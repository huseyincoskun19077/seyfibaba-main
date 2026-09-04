<?php

namespace App\Services;

use App\Models\OrderAddress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BuyerInvoiceService
{
    public const TYPE_INDIVIDUAL = 'individual';
    public const TYPE_CORPORATE = 'corporate';

    public function rules(bool $required = true): array
    {
        $req = $required ? 'required' : 'nullable';

        return [
            'invoice_type' => [$req, 'in:individual,corporate'],
            'tc_identity' => ['nullable', 'string', 'max:11'],
            'tax_number' => ['nullable', 'string', 'max:20'],
            'tax_office' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:191'],
            'is_e_invoice' => ['nullable', 'boolean'],
            'postal_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * @return array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string,company_name:?string,is_e_invoice:bool,postal_code:?string}
     */
    public function validateFromRequest(Request $request, ?User $user = null, bool $required = true): array
    {
        $input = [
            'invoice_type' => $request->input('invoice_type', $user?->invoice_type ?: self::TYPE_INDIVIDUAL),
            'tc_identity' => $this->digitsOnly($request->input('tc_identity', $user?->tc_identity)),
            'tax_number' => $this->digitsOnly($request->input('tax_number', $user?->tax_number)),
            'tax_office' => trim((string) $request->input('tax_office', $user?->tax_office ?? '')),
            'company_name' => trim((string) $request->input('company_name', $user?->company_name ?? '')),
            'is_e_invoice' => $this->toBool($request->input('is_e_invoice', $user?->is_e_invoice ?? false)),
            'postal_code' => $this->digitsOnly($request->input('postal_code', $request->input('zip_code', $user?->zip_code))),
        ];

        return $this->validateNormalized($input, $required);
    }

    /**
     * @param  array<string, mixed>  $addressInfo
     * @return array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string,company_name:?string,is_e_invoice:bool,postal_code:?string}
     */
    public function validateFromAddressInfo(array $addressInfo, bool $required = true): array
    {
        $input = [
            'invoice_type' => $addressInfo['invoice_type'] ?? self::TYPE_INDIVIDUAL,
            'tc_identity' => $this->digitsOnly($addressInfo['tc_identity'] ?? null),
            'tax_number' => $this->digitsOnly($addressInfo['tax_number'] ?? null),
            'tax_office' => trim((string) ($addressInfo['tax_office'] ?? '')),
            'company_name' => trim((string) ($addressInfo['company_name'] ?? '')),
            'is_e_invoice' => $this->toBool($addressInfo['is_e_invoice'] ?? false),
            'postal_code' => $this->digitsOnly($addressInfo['postal_code'] ?? $addressInfo['zip_code'] ?? null),
        ];

        return $this->validateNormalized($input, $required);
    }

    /**
     * @param  array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string,company_name:?string,is_e_invoice:bool,postal_code:?string}  $invoice
     */
    public function syncUser(User $user, array $invoice): void
    {
        $user->invoice_type = $invoice['invoice_type'];
        $user->tc_identity = $invoice['tc_identity'];
        $user->tax_number = $invoice['tax_number'];
        $user->tax_office = $invoice['tax_office'];
        $user->company_name = $invoice['company_name'];
        $user->is_e_invoice = $invoice['is_e_invoice'] ? 1 : 0;
        if (! empty($invoice['postal_code'])) {
            $user->zip_code = $invoice['postal_code'];
        }
        $user->save();
    }

    /**
     * @param  array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string,company_name:?string,is_e_invoice:bool,postal_code:?string}  $invoice
     */
    public function applyToOrderAddress(OrderAddress $orderAddress, array $invoice): void
    {
        $orderAddress->invoice_type = $invoice['invoice_type'];
        $orderAddress->tc_identity = $invoice['tc_identity'];
        $orderAddress->tax_number = $invoice['tax_number'];
        $orderAddress->tax_office = $invoice['tax_office'];
        $orderAddress->company_name = $invoice['company_name'];
        $orderAddress->is_e_invoice = $invoice['is_e_invoice'] ? 1 : 0;

        if (! empty($invoice['postal_code'])) {
            $orderAddress->billing_zip_code = $invoice['postal_code'];
            if (empty($orderAddress->shipping_zip_code)) {
                $orderAddress->shipping_zip_code = $invoice['postal_code'];
            }
        }
    }

    public function profilePayload(?User $user): array
    {
        if (! $user) {
            return [
                'invoice_type' => self::TYPE_INDIVIDUAL,
                'tc_identity' => '',
                'tax_number' => '',
                'tax_office' => '',
                'company_name' => '',
                'is_e_invoice' => false,
                'postal_code' => '',
            ];
        }

        return [
            'invoice_type' => $user->invoice_type ?: self::TYPE_INDIVIDUAL,
            'tc_identity' => (string) ($user->tc_identity ?? ''),
            'tax_number' => (string) ($user->tax_number ?? ''),
            'tax_office' => (string) ($user->tax_office ?? ''),
            'company_name' => (string) ($user->company_name ?? ''),
            'is_e_invoice' => (bool) ($user->is_e_invoice ?? false),
            'postal_code' => (string) ($user->zip_code ?? ''),
        ];
    }

    public function isValidTcKimlik(string $tc): bool
    {
        if (! preg_match('/^[1-9][0-9]{10}$/', $tc)) {
            return false;
        }

        $digits = array_map('intval', str_split($tc));
        $oddSum = $digits[0] + $digits[2] + $digits[4] + $digits[6] + $digits[8];
        $evenSum = $digits[1] + $digits[3] + $digits[5] + $digits[7];
        $digit10 = (($oddSum * 7) - $evenSum) % 10;
        if ($digit10 < 0) {
            $digit10 += 10;
        }
        if ($digits[9] !== $digit10) {
            return false;
        }

        $digit11 = array_sum(array_slice($digits, 0, 10)) % 10;

        return $digits[10] === $digit11;
    }

    public function isValidTaxNumber(string $taxNumber): bool
    {
        if (! preg_match('/^[0-9]{10}$/', $taxNumber)) {
            return false;
        }

        $digits = array_map('intval', str_split($taxNumber));
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $tmp = ($digits[$i] + (9 - $i)) % 10;
            $sum = ($sum + ($tmp * (2 ** (9 - $i)) % 9 ?: 9)) % 10;
        }

        return (10 - ($sum % 10)) % 10 === $digits[9];
    }

    /**
     * @param  array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string,company_name:?string,is_e_invoice:bool,postal_code:?string}  $input
     * @return array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string,company_name:?string,is_e_invoice:bool,postal_code:?string}
     */
    private function validateNormalized(array $input, bool $required): array
    {
        $validator = Validator::make($input, $this->rules($required), [
            'invoice_type.required' => 'Fatura tipi seçilmelidir.',
            'invoice_type.in' => 'Geçersiz fatura tipi.',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        $type = $input['invoice_type'] ?: self::TYPE_INDIVIDUAL;
        $tc = $input['tc_identity'] ?: null;
        $taxNumber = $input['tax_number'] ?: null;
        $taxOffice = $input['tax_office'] ?: null;
        $companyName = $input['company_name'] ?: null;
        $isEInvoice = (bool) ($input['is_e_invoice'] ?? false);
        $postalCode = $input['postal_code'] ?: null;

        if ($type === self::TYPE_INDIVIDUAL) {
            if ($required && empty($tc)) {
                throw ValidationException::withMessages([
                    'tc_identity' => ['TC Kimlik No zorunludur.'],
                ]);
            }
            if ($tc && ! $this->isValidTcKimlik($tc)) {
                throw ValidationException::withMessages([
                    'tc_identity' => ['Geçerli bir TC Kimlik No girin.'],
                ]);
            }
            if ($required && (empty($postalCode) || strlen($postalCode) !== 5)) {
                throw ValidationException::withMessages([
                    'postal_code' => ['5 haneli posta kodu zorunludur.'],
                ]);
            }
            if ($postalCode && ! preg_match('/^[0-9]{5}$/', $postalCode)) {
                throw ValidationException::withMessages([
                    'postal_code' => ['Geçerli bir posta kodu girin.'],
                ]);
            }

            return [
                'invoice_type' => self::TYPE_INDIVIDUAL,
                'tc_identity' => $tc,
                'tax_number' => null,
                'tax_office' => null,
                'company_name' => null,
                'is_e_invoice' => false,
                'postal_code' => $postalCode,
            ];
        }

        if ($required && empty($taxNumber)) {
            throw ValidationException::withMessages([
                'tax_number' => ['VKN/TCKN zorunludur.'],
            ]);
        }
        if ($required && empty($taxOffice)) {
            throw ValidationException::withMessages([
                'tax_office' => ['Vergi dairesi zorunludur.'],
            ]);
        }
        if ($required && empty($companyName)) {
            throw ValidationException::withMessages([
                'company_name' => ['Firma adı zorunludur.'],
            ]);
        }

        $corporateTc = null;
        if ($taxNumber) {
            $len = strlen($taxNumber);
            if ($len === 11) {
                if (! $this->isValidTcKimlik($taxNumber)) {
                    throw ValidationException::withMessages([
                        'tax_number' => ['Geçerli bir TCKN girin.'],
                    ]);
                }
                $corporateTc = $taxNumber;
            } elseif ($len === 10) {
                if (! $this->isValidTaxNumber($taxNumber)) {
                    throw ValidationException::withMessages([
                        'tax_number' => ['Geçerli bir vergi numarası girin.'],
                    ]);
                }
            } else {
                throw ValidationException::withMessages([
                    'tax_number' => ['VKN 10, TCKN 11 haneli olmalıdır.'],
                ]);
            }
        }

        return [
            'invoice_type' => self::TYPE_CORPORATE,
            'tc_identity' => $corporateTc,
            'tax_number' => $taxNumber,
            'tax_office' => $taxOffice,
            'company_name' => $companyName,
            'is_e_invoice' => $isEInvoice,
            'postal_code' => $postalCode,
        ];
    }

    private function digitsOnly(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, [1, '1', 'true', 'on', 'yes'], true);
    }
}
