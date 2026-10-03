<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Country;
use App\Models\CountryState;
use App\Models\User;
use App\Models\Vendor;
use App\Support\PhoneNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateAgencyDemoUsers extends Command
{
    protected $signature = 'demo:agency-accounts';

    protected $description = 'Reklam ajansı için Kuaför Tedarik satıcı ve kuaför (müşteri) demo hesapları oluşturur';

    public function handle(): int
    {
        $sellerPassword = 'KuaforTedarik26Satici';
        $buyerPassword = 'KuaforTedarik26Salon';

        $location = $this->resolveIstanbul();

        $seller = $this->upsertSeller($location, $sellerPassword);
        $buyer = $this->upsertBuyer($location, $buyerPassword);

        $this->newLine();
        $this->info('Reklam ajansı demo hesapları hazır. Admin onayından sonra giriş yapılabilir.');
        $this->newLine();
        $this->line('SATICI');
        $this->line('  Mağaza : Kuaför Tedarik Mağazası');
        $this->line('  Yetkili: Kuaför Tedarik Satış');
        $this->line('  E-posta: '.$seller['email']);
        $this->line('  Telefon: '.$seller['phone']);
        $this->line('  Şifre  : '.$sellerPassword);
        $this->line('  Giriş  : https://kuafortedarik.com/satici-giris');
        $this->line('  Admin  : Bekleyen Satıcılar — aktif et');
        $this->newLine();
        $this->line('KUAFÖR (müşteri)');
        $this->line('  Ad     : Kuaför Tedarik Salon');
        $this->line('  E-posta: '.$buyer['email']);
        $this->line('  Telefon: '.$buyer['phone']);
        $this->line('  Şifre  : '.$buyerPassword);
        $this->line('  Giriş  : https://kuafortedarik.com/login');
        $this->line('  Admin  : Bekleyen Müşteriler — aktif et');

        return self::SUCCESS;
    }

    /**
     * @param  array{country_id: int|null, state_id: int|null, city_id: int|null}  $location
     * @return array{email: string, phone: string}
     */
    protected function upsertSeller(array $location, string $password): array
    {
        $email = 'demo-satici@kuafortedarik.com';
        $phone = $this->uniquePhone('+905551102201', $email);

        return DB::transaction(function () use ($email, $phone, $password, $location) {
            $user = User::query()->where('email', $email)->first() ?: new User();
            $user->name = 'Kuaför Tedarik Satış';
            $user->email = $email;
            $user->phone = $phone;
            $user->password = Hash::make($password);
            $user->status = 1;
            $user->email_verified = 1;
            $user->agree_policy = 1;
            $user->verify_token = null;
            if (Schema::hasColumn('users', 'must_change_password')) {
                $user->must_change_password = 0;
            }
            if (Schema::hasColumn('users', 'country_id')) {
                $user->country_id = $location['country_id'];
                $user->state_id = $location['state_id'];
                $user->city_id = $location['city_id'];
            }
            $user->save();

            $vendor = Vendor::query()->where('user_id', $user->id)->first() ?: new Vendor();
            $vendor->user_id = $user->id;
            $vendor->shop_name = 'Kuaför Tedarik Mağazası';
            $vendor->slug = $this->uniqueShopSlug('Kuaför Tedarik Mağazası', $vendor->id ?? null);
            $vendor->email = $email;
            $vendor->phone = $phone;
            $vendor->address = 'İstanbul';
            $vendor->greeting_msg = 'Kuaför Tedarik Mağazası’na hoş geldiniz';
            $vendor->open_at = '09:00';
            $vendor->closed_at = '19:00';
            $vendor->seo_title = 'Kuaför Tedarik Mağazası';
            $vendor->seo_description = 'Kuaför Tedarik demo satıcı mağazası';
            $vendor->status = 0;
            $vendor->is_featured = 0;
            $vendor->top_rated = 0;
            $vendor->is_verified = 1;
            if (Schema::hasColumn('vendors', 'kyc_status')) {
                $vendor->kyc_status = 'approved';
            }
            if (Schema::hasColumn('vendors', 'kyc_approved_at')) {
                $vendor->kyc_approved_at = now();
                $vendor->kyc_submitted_at = $vendor->kyc_submitted_at ?? now();
            }
            if (Schema::hasColumn('vendors', 'legal_company_title')) {
                $vendor->legal_company_title = 'Kuaför Tedarik Mağazası';
            }
            if (Schema::hasColumn('vendors', 'seller_terms_accepted_at')) {
                $vendor->seller_terms_accepted_at = now();
            }
            if (Schema::hasColumn('vendors', 'quick_registration_note')) {
                $vendor->quick_registration_note = 'Reklam ajansı demo satıcı hesabı';
            }
            $vendor->save();

            return ['email' => $email, 'phone' => $phone];
        });
    }

    /**
     * @param  array{country_id: int|null, state_id: int|null, city_id: int|null}  $location
     * @return array{email: string, phone: string}
     */
    protected function upsertBuyer(array $location, string $password): array
    {
        $email = 'demo-kuafor@kuafortedarik.com';
        $phone = $this->uniquePhone('+905551102202', $email);

        $user = User::query()->where('email', $email)->first() ?: new User();
        $user->name = 'Kuaför Tedarik Salon';
        $user->email = $email;
        $user->phone = $phone;
        $user->password = Hash::make($password);
        $user->status = 0;
        $user->email_verified = 1;
        $user->agree_policy = 1;
        $user->verify_token = null;
        if (Schema::hasColumn('users', 'must_change_password')) {
            $user->must_change_password = 0;
        }
        if (Schema::hasColumn('users', 'country_id')) {
            $user->country_id = $location['country_id'];
            $user->state_id = $location['state_id'];
            $user->city_id = $location['city_id'];
        }
        $user->save();

        if ($user->seller) {
            throw new \RuntimeException('demo-kuafor@kuafortedarik.com bir satıcıya bağlı. Müşteri hesabı oluşturulamadı.');
        }

        return ['email' => $email, 'phone' => $phone];
    }

    /**
     * @return array{country_id: int|null, state_id: int|null, city_id: int|null}
     */
    protected function resolveIstanbul(): array
    {
        $country = Country::query()
            ->where(function ($q) {
                $q->where('name', 'Türkiye')->orWhere('name', 'Turkey');
            })
            ->first();

        $state = $country
            ? CountryState::query()
                ->where('country_id', $country->id)
                ->where(function ($q) {
                    $q->where('name', 'İstanbul')->orWhere('name', 'Istanbul');
                })
                ->first()
            : null;

        $city = $state
            ? City::query()
                ->where('country_state_id', $state->id)
                ->where(function ($q) {
                    $q->where('name', 'Şişli')
                        ->orWhere('name', 'Sisli')
                        ->orWhere('name', 'Kadıköy');
                })
                ->orderByRaw("CASE WHEN name IN ('Şişli','Sisli') THEN 0 ELSE 1 END")
                ->first()
            : null;

        return [
            'country_id' => $country?->id,
            'state_id' => $state?->id,
            'city_id' => $city?->id,
        ];
    }

    protected function uniquePhone(string $preferred, string $keepForEmail): string
    {
        $e164 = PhoneNormalizer::toE164($preferred);
        $owner = User::query()->where('phone', $e164)->first();
        if (! $owner || strcasecmp((string) $owner->email, $keepForEmail) === 0) {
            return $e164;
        }

        for ($i = 3; $i <= 20; $i++) {
            $candidate = PhoneNormalizer::toE164('+9055511022'.str_pad((string) $i, 2, '0', STR_PAD_LEFT));
            if (! User::query()->where('phone', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Boş demo telefon bulunamadı.');
    }

    protected function uniqueShopSlug(string $shopName, mixed $ignoreVendorId): string
    {
        $baseSlug = Str::slug($shopName);
        if ($baseSlug === '') {
            $baseSlug = 'kuafor-tedarik-magazasi';
        }

        $slug = $baseSlug;
        $counter = 1;
        while (
            Vendor::query()
                ->where('slug', $slug)
                ->when($ignoreVendorId, fn ($q) => $q->where('id', '!=', $ignoreVendorId))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
