<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryState;
use App\Services\CallCenter\QuickSellerRegistrationService;
use App\Services\LegalConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PublicSellerRegistrationController extends Controller
{
    public function __construct(
        protected QuickSellerRegistrationService $registrationService,
        protected LegalConsentService $legalConsentService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shop_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'state_id' => 'nullable|integer|exists:country_states,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'category_ids' => 'nullable|array|max:20',
            'category_ids.*' => 'integer|exists:categories,id',
            'legal_consents' => 'required|array|min:1',
            'legal_consents.*.slug' => 'required|string|max:64',
            'legal_consents.*.status' => 'sometimes|boolean',
        ]);

        $this->legalConsentService->assertRequiredSlugs(
            $validated['legal_consents'],
            LegalConsentService::SELLER_REGISTER_REQUIRED_SLUGS
        );

        try {
            $result = $this->registrationService->registerPublic($validated);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $this->legalConsentService->recordMany(
            $request,
            $validated['legal_consents'],
            [
                'user_id' => $result->user->id,
                'context' => 'seller_register',
            ]
        );

        if ($this->legalConsentService->hasAcceptedSlug($validated['legal_consents'], 'seller-terms')) {
            $result->vendor->seller_terms_accepted_at = now();
            $result->vendor->seller_terms_accepted_ip = $request->ip();
            $result->vendor->save();
        }

        $hasRealEmail = $result->user->email
            && ! QuickSellerRegistrationService::isPendingEmail($result->user->email);

        if ($hasRealEmail) {
            try {
                \App\Helpers\MailHelper::setMailConfig();
                $shopName = $result->vendor->shop_name ?? 'Mağazanız';
                $content = "Seyfibaba'ya hoş geldiniz!\n\nMağaza: {$shopName}\n\nKaydınız alındı ve admin onayı bekleniyor. Onaylandığında size bilgilendirme yapılacaktır.\n\nSatıcı panelinize giriş yapmak için: seyfibaba.com/satici-giris\n\nSorularınız için bize ulaşabilirsiniz.";
                \Mail::to($result->user->email)->send(new \App\Mail\SellerWelcomeMail($content));
            } catch (\Throwable $e) {
                \Log::warning('Seller welcome mail failed', ['user_id' => $result->user->id, 'error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'message' => $this->buildSuccessMessage($result, $hasRealEmail),
            'data' => [
                'shop_name' => $result->vendor->shop_name,
                'phone' => $result->user->phone,
                'email' => $hasRealEmail ? $result->user->email : null,
                'sms_sent' => $result->smsSent,
                'email_sent' => $result->emailSent,
                'login_url' => '/satici-giris',
            ],
        ], 201);
    }

    public function turkiyeStates(): JsonResponse
    {
        $country = Country::query()->where('slug', 'turkiye')->first();

        if (! $country) {
            return response()->json(['states' => []]);
        }

        $states = CountryState::query()
            ->where('country_id', $country->id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['states' => $states]);
    }

    protected function buildSuccessMessage(object $result, bool $hasRealEmail): string
    {
        if ($result->smsSent && $hasRealEmail && $result->emailSent) {
            return 'Kaydınız oluşturuldu. Giriş bilgileriniz SMS ve e-posta ile gönderildi.';
        }

        if ($result->smsSent) {
            return 'Kaydınız oluşturuldu. Telefonunuza gönderilen şifre ile satıcı girişi yapabilirsiniz.';
        }

        return 'Kaydınız oluşturuldu ancak SMS gönderilemedi. Lütfen destek ile iletişime geçin.';
    }
}
