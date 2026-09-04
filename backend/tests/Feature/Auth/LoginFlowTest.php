<?php

namespace Tests\Feature\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use App\Models\Vendor;
use App\Services\SmsServiceInterface;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Config::set('jwt.secret', 'testing-jwt-secret-key-32chars-min!!');
        Config::set('jwt.algo', 'HS256');
        Config::set('sms.otp.length', 6);
        Config::set('sms.otp.expire_minutes', 5);
        Config::set('sms.otp.max_attempts', 3);
        Config::set('sms.otp.cooldown_seconds', 60);
        Config::set('cache.default', 'array');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->integer('status')->default(1);
            $table->integer('email_verified')->default(1);
            $table->integer('agree_policy')->default(1);
            $table->string('forget_password_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('shop_name')->nullable();
            $table->string('slug')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->integer('status')->default(1);
            $table->string('registration_source')->default('self');
            $table->timestamp('seller_terms_accepted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('otp_code', 6);
            $table->string('purpose')->default('register');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        $this->app->bind(SmsServiceInterface::class, fn () => new class implements SmsServiceInterface {
            public function send(string $phone, string $message): bool
            {
                return true;
            }

            public function sendTransactional(string $phone, string $message): bool
            {
                return true;
            }
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    protected function createBuyer(array $overrides = []): User
    {
        $user = new User();
        $user->forceFill(array_merge([
            'name' => 'Alıcı Test',
            'email' => 'buyer@example.com',
            'phone' => '+905321111111',
            'password' => Hash::make('BuyerPass1'),
            'status' => 1,
            'email_verified' => 1,
            'must_change_password' => 0,
            'agree_policy' => 1,
        ], $overrides))->save();

        return $user->fresh();
    }

    protected function createSeller(array $userOverrides = [], array $vendorOverrides = []): array
    {
        $user = $this->createBuyer(array_merge([
            'name' => 'Satıcı Test',
            'email' => 'seller@example.com',
            'phone' => '+905322222222',
            'password' => Hash::make('SellerPass1'),
        ], $userOverrides));

        $vendor = new Vendor();
        $vendor->forceFill(array_merge([
            'user_id' => $user->id,
            'shop_name' => 'Test Magaza',
            'slug' => 'test-magaza-'.$user->id,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => 1,
            'registration_source' => 'call_center',
            'seller_terms_accepted_at' => now(),
        ], $vendorOverrides))->save();

        return [$user->fresh(), $vendor->fresh()];
    }

    public function test_buyer_can_login_with_email_and_password(): void
    {
        $this->createBuyer();

        $response = $this->postJson('/api/store-login', [
            'email' => 'buyer@example.com',
            'password' => 'BuyerPass1',
        ]);

        $response->assertOk()
            ->assertJsonPath('is_vendor', 0)
            ->assertJsonStructure(['access_token', 'token_type', 'user']);
    }

    public function test_buyer_can_login_with_phone_and_password(): void
    {
        $this->createBuyer();

        $response = $this->postJson('/api/store-login', [
            'email' => '5321111111',
            'password' => 'BuyerPass1',
        ]);

        $response->assertOk()
            ->assertJsonPath('is_vendor', 0)
            ->assertJsonStructure(['access_token']);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->createBuyer();

        $response = $this->postJson('/api/store-login', [
            'email' => 'buyer@example.com',
            'password' => 'WrongPass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'E-posta veya şifre hatalı.');
    }

    public function test_inactive_account_cannot_login(): void
    {
        $this->createBuyer(['status' => 0]);

        $response = $this->postJson('/api/store-login', [
            'email' => 'buyer@example.com',
            'password' => 'BuyerPass1',
        ]);

        $response->assertStatus(403);
    }

    public function test_unverified_email_blocks_email_login_for_buyer(): void
    {
        $this->createBuyer(['email_verified' => 0]);

        $response = $this->postJson('/api/store-login', [
            'email' => 'buyer@example.com',
            'password' => 'BuyerPass1',
        ]);

        $response->assertStatus(402)
            ->assertJsonPath('error_code', 'email_verification_required');
    }

    public function test_phone_login_allowed_when_email_unverified(): void
    {
        $this->createBuyer(['email_verified' => 0]);

        $response = $this->postJson('/api/store-login', [
            'email' => '5321111111',
            'password' => 'BuyerPass1',
        ]);

        $response->assertOk()->assertJsonStructure(['access_token']);
    }

    public function test_seller_first_login_accepts_otp_code(): void
    {
        [$user] = $this->createSeller([
            'password' => Hash::make('random-old-hash'),
            'must_change_password' => 1,
        ]);

        OtpVerification::create([
            'phone' => $user->phone,
            'otp_code' => '654321',
            'purpose' => 'seller_first_login',
            'attempts' => 0,
            'max_attempts' => 50,
            'expires_at' => Carbon::now()->addYears(1),
        ]);

        $response = $this->postJson('/api/store-login', [
            'email' => 'seller@example.com',
            'password' => '654321',
        ]);

        $response->assertOk()
            ->assertJsonPath('force_password_change', true)
            ->assertJsonStructure(['access_token', 'redirect_url', 'notification']);

        $this->assertNotEmpty($response->json('access_token'));
        $this->assertNotEmpty($response->json('redirect_url'));
    }

    public function test_seller_can_login_with_real_password_when_must_change_flag_stuck(): void
    {
        [$user] = $this->createSeller([
            'password' => Hash::make('NewSeller1'),
            'must_change_password' => 1,
        ]);

        OtpVerification::create([
            'phone' => $user->phone,
            'otp_code' => '111222',
            'purpose' => 'seller_first_login',
            'attempts' => 0,
            'max_attempts' => 50,
            'expires_at' => Carbon::now()->addYears(1),
        ]);

        $response = $this->postJson('/api/store-login', [
            'email' => 'seller@example.com',
            'password' => 'NewSeller1',
        ]);

        $response->assertOk()
            ->assertJsonPath('is_vendor', 1)
            ->assertJsonStructure(['access_token']);

        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    public function test_seller_wrong_otp_does_not_accept_random_password(): void
    {
        [$user] = $this->createSeller([
            'password' => Hash::make('OtherPass1'),
            'must_change_password' => 1,
        ]);

        OtpVerification::create([
            'phone' => $user->phone,
            'otp_code' => '777888',
            'purpose' => 'seller_first_login',
            'attempts' => 0,
            'max_attempts' => 50,
            'expires_at' => Carbon::now()->addYears(1),
        ]);

        $response = $this->postJson('/api/store-login', [
            'email' => 'seller@example.com',
            'password' => 'NotTheOtp1',
        ]);

        $response->assertStatus(402);
        $this->assertStringContainsString('Tek kullanımlık', (string) $response->json('notification'));
        $this->assertSame(1, (int) OtpVerification::where('phone', $user->phone)->value('attempts'));
    }

    public function test_password_reset_otp_flow_unlocks_seller_login(): void
    {
        [$user] = $this->createSeller([
            'password' => Hash::make('OldPass12'),
            'must_change_password' => 1,
            'phone' => '+905323333333',
        ]);

        OtpVerification::create([
            'phone' => '+905323333333',
            'otp_code' => '123456',
            'purpose' => 'seller_first_login',
            'attempts' => 0,
            'max_attempts' => 50,
            'expires_at' => Carbon::now()->addYears(1),
        ]);

        OtpVerification::create([
            'phone' => '+905323333333',
            'otp_code' => '999888',
            'purpose' => 'password_reset',
            'attempts' => 0,
            'max_attempts' => 3,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $verify = $this->postJson('/api/auth/otp/verify', [
            'phone' => '+905323333333',
            'otp_code' => '999888',
            'purpose' => 'password_reset',
        ]);
        $verify->assertOk();
        $token = $verify->json('token');
        $this->assertNotEmpty($token);

        $reset = $this->postJson('/api/store-reset-password/'.$token, [
            'phone' => '+905323333333',
            'otp_verified_token' => $token,
            'password' => 'FreshPass1',
            'password_confirmation' => 'FreshPass1',
        ]);
        $reset->assertOk();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
        $this->assertSame(0, OtpVerification::where('purpose', 'seller_first_login')->where('phone', '+905323333333')->count());

        $login = $this->postJson('/api/store-login', [
            'email' => 'seller@example.com',
            'password' => 'FreshPass1',
        ]);
        $login->assertOk()->assertJsonStructure(['access_token']);
    }

    public function test_password_reset_rejects_expired_verified_token(): void
    {
        $this->createBuyer(['phone' => '+905324444444']);

        $reset = $this->postJson('/api/store-reset-password/deadtoken', [
            'phone' => '+905324444444',
            'otp_verified_token' => 'deadtoken',
            'password' => 'FreshPass1',
            'password_confirmation' => 'FreshPass1',
        ]);

        $reset->assertStatus(422);
        $this->assertStringContainsString('geçersiz veya süresi dolmuş', (string) $reset->json('notification'));
    }

    public function test_password_reset_otp_send_does_not_create_otp_for_unknown_phone(): void
    {
        $response = $this->postJson('/api/auth/otp/send', [
            'phone' => '+905329999999',
            'purpose' => 'password_reset',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('otp_verifications', 0);
    }

    public function test_seller_web_login_with_otp_then_password_after_unlock(): void
    {
        [$user] = $this->createSeller([
            'email' => 'webseller@example.com',
            'phone' => '+905325555555',
            'password' => Hash::make('WebPass12'),
            'must_change_password' => 1,
        ]);

        OtpVerification::create([
            'phone' => $user->phone,
            'otp_code' => '555666',
            'purpose' => 'seller_first_login',
            'attempts' => 0,
            'max_attempts' => 50,
            'expires_at' => Carbon::now()->addYears(1),
        ]);

        $controller = app(\App\Http\Controllers\WEB\Seller\Auth\SellerLoginController::class);

        $otpLogin = $controller->storeLogin(Request::create('/seller/login', 'POST', [
            'login' => 'webseller@example.com',
            'password' => '555666',
        ]));
        $otpPayload = $otpLogin->getData(true);
        $this->assertTrue((bool) ($otpPayload['force_password_change'] ?? false));
        $this->assertNotEmpty($otpPayload['success'] ?? null);

        $user->password = Hash::make('WebPass12');
        $user->must_change_password = 1;
        $user->save();

        OtpVerification::create([
            'phone' => $user->phone,
            'otp_code' => '555667',
            'purpose' => 'seller_first_login',
            'attempts' => 0,
            'max_attempts' => 50,
            'expires_at' => Carbon::now()->addYears(1),
        ]);

        $passwordLogin = $controller->storeLogin(Request::create('/seller/login', 'POST', [
            'login' => 'webseller@example.com',
            'password' => 'WebPass12',
        ]));
        $passwordPayload = $passwordLogin->getData(true);
        $this->assertNotEmpty($passwordPayload['success'] ?? null);
        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }

    public function test_seller_web_login_rejects_non_seller_user(): void
    {
        $this->createBuyer(['email' => 'notseller@example.com']);

        $controller = app(\App\Http\Controllers\WEB\Seller\Auth\SellerLoginController::class);
        $response = $controller->storeLogin(Request::create('/seller/login', 'POST', [
            'login' => 'notseller@example.com',
            'password' => 'BuyerPass1',
        ]));

        $payload = $response->getData(true);
        $this->assertNotEmpty($payload['error'] ?? null);
    }

    public function test_unknown_email_returns_account_not_found(): void
    {
        $response = $this->postJson('/api/store-login', [
            'email' => 'missing@example.com',
            'password' => 'Whatever1',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('error_code', 'account_not_found');
    }
}
