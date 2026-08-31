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
        ];
    }

    /**
     * @return array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string}
     */
    public function validateFromRequest(Request $request, ?User $user = null, bool $required = true): array
    {
        $input = [
            'invoice_type' => $request->input('invoice_type', $user?->invoice_type ?: self::TYPE_INDIVIDUAL),
            'tc_identity' => $this->digitsOnly($request->input('tc_identity', $user?->tc_identity)),
            'tax_number' => $this->digitsOnly($request->input('tax_number', $user?->tax_number)),
            'tax_office' => trim((string) $request->input('tax_office', $user?->tax_office ?? '')),
        ];

        return $this->validateNormalized($input, $required);
    }

    /**
     * @param  array<string, mixed>  $addressInfo
     * @return array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string}
     */
    public function validateFromAddressInfo(array $addressInfo, bool $required = true): array
    {
        $input = [
            'invoice_type' => $addressInfo['invoice_type'] ?? self::TYPE_INDIVIDUAL,
            'tc_identity' => $this->digitsOnly($addressInfo['tc_identity'] ?? null),
            'tax_number' => $this->digitsOnly($addressInfo['tax_number'] ?? null),
            'tax_office' => trim((string) ($addressInfo['tax_office'] ?? '')),
        ];

        return $this->validateNormalized($input, $required);
    }

    /**
     * @param  array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string}  $invoice
     */
    public function syncUser(User $user, array $invoice): void
    {
        $user->invoice_type = $invoice['invoice_type'];
        $user->tc_identity = $invoice['invoice_type'] === self::TYPE_INDIVIDUAL
            ? $invoice['tc_identity']
            : null;
        $user->tax_number = $invoice['invoice_type'] === self::TYPE_CORPORATE
            ? $invoice['tax_number']
            : null;
        $user->tax_office = $invoice['invoice_type'] === self::TYPE_CORPORATE
            ? $invoice['tax_office']
            : null;
        $user->save();
    }

    /**
     * @param  array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string}  $invoice
     */
    public function applyToOrderAddress(OrderAddress $orderAddress, array $invoice): void
    {
        $orderAddress->invoice_type = $invoice['invoice_type'];
        $orderAddress->tc_identity = $invoice['tc_identity'];
        $orderAddress->tax_number = $invoice['tax_number'];
        $orderAddress->tax_office = $invoice['tax_office'];
    }

    public function profilePayload(?User $user): array
    {
        if (! $user) {
            return [
                'invoice_type' => self::TYPE_INDIVIDUAL,
                'tc_identity' => '',
                'tax_number' => '',
                'tax_office' => '',
            ];
        }

        return [
            'invoice_type' => $user->invoice_type ?: self::TYPE_INDIVIDUAL,
            'tc_identity' => (string) ($user->tc_identity ?? ''),
            'tax_number' => (string) ($user->tax_number ?? ''),
            'tax_office' => (string) ($user->tax_office ?? ''),
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
     * @param  array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string}  $input
     * @return array{invoice_type:string,tc_identity:?string,tax_number:?string,tax_office:?string}
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

            return [
                'invoice_type' => self::TYPE_INDIVIDUAL,
                'tc_identity' => $tc,
                'tax_number' => null,
                'tax_office' => null,
            ];
        }

        if ($required && empty($taxNumber)) {
            throw ValidationException::withMessages([
                'tax_number' => ['Vergi numarası zorunludur.'],
            ]);
        }
        if ($required && empty($taxOffice)) {
            throw ValidationException::withMessages([
                'tax_office' => ['Vergi dairesi zorunludur.'],
            ]);
        }
        if ($taxNumber && ! $this->isValidTaxNumber($taxNumber)) {
            throw ValidationException::withMessages([
                'tax_number' => ['Geçerli bir vergi numarası girin.'],
            ]);
        }

        return [
            'invoice_type' => self::TYPE_CORPORATE,
            'tc_identity' => null,
            'tax_number' => $taxNumber,
            'tax_office' => $taxOffice,
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
}
