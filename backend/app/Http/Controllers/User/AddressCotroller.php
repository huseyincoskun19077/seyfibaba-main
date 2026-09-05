<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\Country;
use App\Models\CountryState;
use App\Models\City;
use App\Models\Setting;
use App\Models\User;
use Auth;
use Illuminate\Validation\ValidationException;

class AddressCotroller extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        $user = Auth::guard('api')->user();
        $addresses = Address::with('country', 'countryState', 'city')->where(['user_id' => $user->id])->get();

        return response()->json(['addresses' => $addresses]);
    }

    public function create()
    {
        $countries = Country::orderBy('name', 'asc')->where('status', 1)->select('id', 'name')->get();

        return response()->json(['countries' => $countries]);
    }

    public function store(Request $request)
    {
        $this->validateAddressRequest($request);
        // Fatura alanları yalnızca gönderildiyse zorunlu (teslimat adresi TC gerektirmez)
        if ($request->filled('invoice_type') || $request->has('tc_identity') || $request->has('tax_number')) {
            app(\App\Services\BuyerInvoiceService::class)->validateFromRequest($request, null, true);
        }

        $user = Auth::guard('api')->user();
        $email = $this->normalizeEmail($request->email);
        $this->assertEmailAvailable($email, $user->id);
        $this->syncUserEmailIfNeeded($user, $email);

        $isExist = Address::where(['user_id' => $user->id])->count();
        $address = new Address();
        $address->user_id = $user->id;
        $address->name = $request->name;
        $address->email = $email;
        $address->phone = $request->phone;
        $address->address = $request->address;
        $address->neighborhood = $request->filled('neighborhood')
            ? trim((string) $request->neighborhood)
            : null;
        $address->country_id = $request->country;
        $address->state_id = $request->state;
        $address->city_id = $request->city;
        $address->type = $request->type;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $this->applyInvoiceFields($address, $request);
        if ($isExist == 0) {
            $address->default_billing = 1;
            $address->default_shipping = 1;
        }
        $address->save();

        $address = Address::with('country', 'countryState', 'city')->where(['id' => $address->id])->first();

        return response()->json([
            'notification' => 'Adres başarıyla eklendi',
            'address' => $address,
        ]);
    }

    public function show($id)
    {
        $user = Auth::guard('api')->user();
        $address = Address::with('country', 'countryState', 'city')->where(['user_id' => $user->id, 'id' => $id])->first();
        if (! $address) {
            return response()->json(['notification' => 'Adres bulunamadı'], 403);
        }

        return response()->json(['address' => $address]);
    }

    public function edit($id)
    {
        $user = Auth::guard('api')->user();
        $address = Address::where(['user_id' => $user->id, 'id' => $id])->first();
        if (! $address) {
            return response()->json(['notification' => 'Adres bulunamadı'], 403);
        }
        $countries = Country::orderBy('name', 'asc')->where('status', 1)->select('id', 'name')->get();
        $states = CountryState::orderBy('name', 'asc')->where(['status' => 1, 'country_id' => $address->country_id])->get();
        $cities = City::orderBy('name', 'asc')->where(['status' => 1, 'country_state_id' => $address->state_id])->get();

        return response()->json([
            'address' => $address,
            'countries' => $countries,
            'states' => $states,
            'cities' => $cities,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::guard('api')->user();
        $address = Address::where(['user_id' => $user->id, 'id' => $id])->first();
        if (! $address) {
            return response()->json(['notification' => 'Adres bulunamadı'], 403);
        }

        $this->validateAddressRequest($request);
        if ($request->filled('invoice_type') || $request->has('tc_identity') || $request->has('tax_number')) {
            app(\App\Services\BuyerInvoiceService::class)->validateFromRequest($request, null, true);
        }

        $email = $this->normalizeEmail($request->email);
        $this->assertEmailAvailable($email, $user->id);
        $this->syncUserEmailIfNeeded($user, $email);

        $address->name = $request->name;
        $address->email = $email;
        $address->phone = $request->phone;
        $address->address = $request->address;
        if ($request->has('neighborhood')) {
            $neighborhood = trim((string) $request->neighborhood);
            $address->neighborhood = $neighborhood !== '' ? $neighborhood : null;
        }
        $address->country_id = $request->country;
        $address->state_id = $request->state;
        $address->city_id = $request->city;
        $address->type = $request->type;
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $this->applyInvoiceFields($address, $request);
        $address->save();

        $address = Address::with('country', 'countryState', 'city')->where(['id' => $address->id])->first();

        return response()->json([
            'notification' => 'Adres başarıyla güncellendi',
            'address' => $address,
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::guard('api')->user();
        $address = Address::where(['user_id' => $user->id, 'id' => $id])->first();
        if (! $address) {
            return response()->json(['notification' => 'Adres bulunamadı'], 403);
        }

        if ($address->default_billing == 1 && $address->default_shipping == 1) {
            return response()->json(['notification' => 'Varsayılan adres silinemez.'], 403);
        }

        $address->delete();

        return response()->json(['notification' => 'Adres silindi']);
    }

    private function validateAddressRequest(Request $request): void
    {
        $setting = Setting::first();
        $rules = [
            'name' => 'required',
            'email' => 'required|email:filter',
            'phone' => 'required',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required',
            'address' => 'required',
            'type' => 'required',
            'neighborhood' => 'nullable|string|max:191',
        ];
        $customMessages = [
            'name.required' => 'Ad soyad zorunludur.',
            'email.required' => 'E-posta zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'phone.required' => 'Telefon zorunludur.',
            'country.required' => 'Ülke zorunludur.',
            'state.required' => 'İl zorunludur.',
            'city.required' => 'İlçe zorunludur.',
            'address.required' => 'Açık adres zorunludur.',
            'type.required' => 'Adres tipi zorunludur.',
            'latitude.required' => 'Konum (enlem) zorunludur.',
            'longitude.required' => 'Konum (boylam) zorunludur.',
        ];

        if ((int) ($setting->map_status ?? 0) === 1) {
            $rules['latitude'] = 'required';
            $rules['longitude'] = 'required';
        }

        $this->validate($request, $rules, $customMessages);

        $email = $this->normalizeEmail($request->email);
        if (! $this->isUsableEmail($email)) {
            throw ValidationException::withMessages([
                'email' => ['Geçerli bir e-posta adresi girin. Geçici e-posta kabul edilmez.'],
            ]);
        }
    }

    private function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function isUsableEmail(?string $email): bool
    {
        $email = $this->normalizeEmail($email);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        if (str_ends_with($email, '@pending.seyfibaba.local') || str_ends_with($email, '.local')) {
            return false;
        }

        return true;
    }

    private function assertEmailAvailable(string $email, int $currentUserId): void
    {
        $exists = User::where('email', $email)
            ->where('id', '!=', $currentUserId)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'email' => ['Bu e-posta adresi başka bir hesapta kayıtlı. Farklı bir e-posta girin.'],
            ]);
        }
    }

    private function syncUserEmailIfNeeded(User $user, string $email): void
    {
        $current = $this->normalizeEmail($user->email);
        if (! $this->isUsableEmail($current)) {
            $user->email = $email;
            $user->save();
        }
    }

    private function applyInvoiceFields(Address $address, Request $request): void
    {
        if ($request->has('zip_code') || $request->has('postal_code')) {
            $zip = preg_replace('/\D+/', '', (string) ($request->input('postal_code', $request->input('zip_code'))));
            $address->zip_code = $zip !== '' ? $zip : null;
        }

        if (! $request->filled('invoice_type') && ! $request->has('tc_identity') && ! $request->has('tax_number')) {
            return;
        }

        $invoice = app(\App\Services\BuyerInvoiceService::class)->validateFromRequest($request, null, true);
        $address->invoice_type = $invoice['invoice_type'];
        $address->tc_identity = $invoice['tc_identity'];
        $address->tax_number = $invoice['tax_number'];
        $address->tax_office = $invoice['tax_office'];
        $address->company_name = $invoice['company_name'];
        $address->is_e_invoice = $invoice['is_e_invoice'] ? 1 : 0;
        if (! empty($invoice['postal_code'])) {
            $address->zip_code = $invoice['postal_code'];
        }
    }
}
