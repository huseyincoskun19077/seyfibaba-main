<?php

namespace Tests\Unit\CallCenter;

use App\Mail\CallCenterSellerWelcomeMail;
use App\Models\Admin;
use App\Models\OtpVerification;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CallCenter\QuickSellerRegistrationService;
use App\Services\SmsServiceInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class QuickSellerRegistrationServiceTest extends TestCase
{
    use UsesInMemorySqlite;
    use WithFaker;

    protected QuickSellerRegistrationService $service;
    protected array $sentSmsMessages = [];
    protected object $smsCollector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('admin_type')->default(0);
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->boolean('must_change_password')->default(false);
            $table->integer('status')->default(1);
            $table->integer('email_verified')->default(0);
            $table->integer('agree_policy')->default(0);
            $table->string('verify_token')->nullable();
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

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('shop_name')->nullable();
            $table->string('slug')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('greeting_msg')->nullable();
            $table->string('open_at')->nullable();
            $table->string('closed_at')->nullable();
            $table->text('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->integer('status')->default(0);
            $table->string('registration_source')->default('self');
            $table->unsignedBigInteger('registered_by_admin_id')->nullable();
            $table->text('quick_registration_note')->nullable();
            $table->boolean('welcome_sms_sent')->nullable();
            $table->timestamp('welcome_sms_sent_at')->nullable();
            $table->boolean('welcome_email_sent')->nullable();
            $table->timestamp('welcome_email_sent_at')->nullable();
            $table->unsignedBigInteger('primary_category_id')->nullable();
            $table->json('registration_category_ids')->nullable();
            $table->string('kyc_status')->nullable();
            $table->timestamps();
        });

        $this->sentSmsMessages = [];
        $this->smsCollector = (object) ['messages' => &$this->sentSmsMessages];

        $collector = $this->smsCollector;
        $this->app->bind(SmsServiceInterface::class, fn () => new class($collector) implements SmsServiceInterface {
            public function __construct(private object $collector)
            {
            }

            public function send(string $phone, string $message): bool
            {
                $this->collector->messages[] = [
                    'phone' => $phone,
                    'message' => $message,
                    'channel' => 'otp',
                ];

                return true;
            }

            public function sendTransactional(string $phone, string $message): bool
            {
                $this->collector->messages[] = [
                    'phone' => $phone,
                    'message' => $message,
                    'channel' => 'transactional',
                ];

                return true;
            }
        });

        Mail::fake();

        $this->service = app(QuickSellerRegistrationService::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
        parent::tearDown();
    }

    protected function createCallCenterAgent(): Admin
    {
        $admin = new Admin();
        $admin->name = 'Call Center Agent';
        $admin->email = 'agent@seyfibaba.test';
        $admin->password = Hash::make('secret');
        $admin->admin_type = Admin::TYPE_CALL_CENTER;
        $admin->status = 1;
        $admin->save();

        return $admin;
    }

    public function test_registers_new_seller_with_active_user_and_vendor(): void
    {
        $agent = $this->createCallCenterAgent();

        $result = $this->service->register($agent, [
            'shop_name' => 'Test Mağaza',
            'contact_name' => 'Ahmet Yılmaz',
            'phone' => '5321234567',
            'email' => 'ahmet@example.com',
            'note' => 'Telefon görüşmesi',
        ]);

        $this->assertTrue($result->smsSent);
        $this->assertTrue($result->emailSent);
        $this->assertFalse($result->wasExistingUser);
        $this->assertSame(1, (int) $result->user->status);
        $this->assertTrue((bool) $result->user->must_change_password);
        $this->assertSame(1, (int) $result->vendor->status);
        $this->assertSame('call_center', $result->vendor->registration_source);
        $this->assertSame($agent->id, (int) $result->vendor->registered_by_admin_id);
        $this->assertTrue((bool) $result->vendor->welcome_sms_sent);
        $this->assertNotNull($result->vendor->welcome_sms_sent_at);
        $this->assertTrue((bool) $result->vendor->welcome_email_sent);
        $this->assertFalse(Hash::check($result->otpCode, $result->user->password));
        $this->assertSame('ahmet@example.com', $result->user->email);
        $this->assertSame(1, OtpVerification::where('phone', '+905321234567')->where('purpose', 'seller_first_login')->count());
        $this->assertNotEmpty($this->sentSmsMessages);
        $this->assertSame('transactional', $this->sentSmsMessages[0]['channel']);
        $this->assertStringContainsString('Hosgeldiniz!', $this->sentSmsMessages[0]['message']);
        $this->assertStringContainsString('Kullanici Adiniz: 5321234567', $this->sentSmsMessages[0]['message']);
        $this->assertStringContainsString('Sifreniz:'.$result->otpCode, $this->sentSmsMessages[0]['message']);
        $this->assertStringContainsString('seyfibaba.com/satici-giris', $this->sentSmsMessages[0]['message']);
        $this->assertStringNotContainsString('Gecerlilik suresi', $this->sentSmsMessages[0]['message']);

        $otp = OtpVerification::where('phone', '+905321234567')->where('purpose', 'seller_first_login')->first();
        $this->assertNotNull($otp);
        $this->assertTrue($otp->expires_at->greaterThan(now()->addYears(50)));
        $this->assertSame(50, (int) $otp->max_attempts);

        Mail::assertSent(CallCenterSellerWelcomeMail::class, function (CallCenterSellerWelcomeMail $mail) {
            return $mail->hasTo('ahmet@example.com')
                && $mail->email === 'ahmet@example.com'
                && $mail->loginUrl !== '';
        });
    }

    public function test_registers_new_seller_without_email_using_sms_only(): void
    {
        $agent = $this->createCallCenterAgent();

        $result = $this->service->register($agent, [
            'shop_name' => 'SMS Mağaza',
            'contact_name' => 'Ayşe Demir',
            'phone' => '5329876543',
            'email' => '',
        ]);

        $this->assertTrue($result->smsSent);
        $this->assertFalse($result->emailSent);
        $this->assertTrue((bool) $result->vendor->welcome_sms_sent);
        $this->assertNull($result->vendor->welcome_email_sent);
        $this->assertTrue(QuickSellerRegistrationService::isPendingEmail($result->user->email));
        $this->assertNull($result->vendor->email);
        $this->assertSame(0, (int) $result->user->email_verified);
        Mail::assertNothingSent();
    }

    public function test_adds_vendor_to_existing_customer_with_provided_email(): void
    {
        $agent = $this->createCallCenterAgent();

        $existingUser = new User();
        $existingUser->name = 'Mevcut Müşteri';
        $existingUser->email = 'musteri@example.com';
        $existingUser->phone = '+905321234567';
        $existingUser->password = Hash::make('old-password');
        $existingUser->status = 1;
        $existingUser->email_verified = 1;
        $existingUser->agree_policy = 1;
        $existingUser->save();

        $result = $this->service->register($agent, [
            'shop_name' => 'Mevcut Mağaza',
            'contact_name' => 'Mevcut Müşteri',
            'phone' => '5321234567',
            'email' => 'musteri@example.com',
        ]);

        $this->assertTrue($result->wasExistingUser);
        $this->assertSame('musteri@example.com', $result->user->email);
        $this->assertSame(1, Vendor::where('user_id', $existingUser->id)->count());
        $this->assertFalse(Hash::check('old-password', $result->user->fresh()->password));
        $this->assertTrue((bool) $result->user->fresh()->must_change_password);
        Mail::assertSent(CallCenterSellerWelcomeMail::class);
    }

    public function test_rejects_when_phone_already_has_vendor(): void
    {
        $agent = $this->createCallCenterAgent();

        $user = new User();
        $user->name = 'Satıcı';
        $user->email = 'satici@example.com';
        $user->phone = '+905559998877';
        $user->password = Hash::make('password');
        $user->status = 1;
        $user->save();

        $vendor = new Vendor();
        $vendor->user_id = $user->id;
        $vendor->shop_name = 'Var Olan';
        $vendor->status = 1;
        $vendor->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bu telefon numarasına bağlı bir satıcı hesabı zaten var.');

        $this->service->register($agent, [
            'shop_name' => 'Yeni Mağaza',
            'contact_name' => 'Test',
            'phone' => '5559998877',
            'email' => 'yeni@example.com',
        ]);
    }

    public function test_resend_first_login_sms_keeps_same_otp(): void
    {
        $agent = $this->createCallCenterAgent();

        $result = $this->service->register($agent, [
            'shop_name' => 'Resend Mağaza',
            'contact_name' => 'Ali',
            'phone' => '5324445566',
            'email' => '',
        ]);

        $oldOtp = OtpVerification::where('phone', '+905324445566')->where('purpose', 'seller_first_login')->first();
        $this->assertNotNull($oldOtp);

        $this->sentSmsMessages = [];
        $this->smsCollector->messages = &$this->sentSmsMessages;

        $this->assertTrue($this->service->resendFirstLoginSms($result->vendor->fresh()->load('user')));

        $otps = OtpVerification::where('purpose', 'seller_first_login')
            ->where('phone', '+905324445566')
            ->get();
        $this->assertCount(1, $otps);
        $this->assertSame($oldOtp->otp_code, $otps->first()->otp_code);
        $this->assertNull($otps->first()->verified_at);
        $this->assertNotEmpty($this->sentSmsMessages);
        $this->assertSame('transactional', $this->sentSmsMessages[0]['channel']);
        $this->assertStringContainsString('Sifreniz:'.$oldOtp->otp_code, $this->sentSmsMessages[0]['message']);
        $this->assertTrue((bool) $result->vendor->fresh()->welcome_sms_sent);
    }

    public function test_update_registration_phone_moves_otp_and_sends_welcome_sms(): void
    {
        $agent = $this->createCallCenterAgent();

        $result = $this->service->register($agent, [
            'shop_name' => 'Phone Update Mağaza',
            'contact_name' => 'Mehmet',
            'phone' => '5321112233',
            'email' => '',
        ]);

        $oldOtp = OtpVerification::where('phone', '+905321112233')->where('purpose', 'seller_first_login')->first();
        $this->assertNotNull($oldOtp);

        $this->sentSmsMessages = [];
        $this->smsCollector->messages = &$this->sentSmsMessages;

        $this->assertTrue(
            $this->service->updateRegistration($result->vendor->fresh()->load('user'), [
                'shop_name' => 'Phone Update Mağaza',
                'contact_name' => 'Mehmet',
                'phone' => '5329998877',
                'send_sms' => true,
            ])
        );

        $user = $result->user->fresh();
        $vendor = $result->vendor->fresh();
        $this->assertSame('+905329998877', $user->phone);
        $this->assertSame('+905329998877', $vendor->phone);
        $this->assertTrue(QuickSellerRegistrationService::isPendingEmail($user->email));
        $this->assertStringContainsString('5329998877', $user->email);

        $otp = OtpVerification::where('phone', '+905329998877')->where('purpose', 'seller_first_login')->first();
        $this->assertNotNull($otp);
        $this->assertSame($oldOtp->otp_code, $otp->otp_code);
        $this->assertNull(OtpVerification::where('phone', '+905321112233')->where('purpose', 'seller_first_login')->first());
        $this->assertNotEmpty($this->sentSmsMessages);
        $this->assertStringContainsString('Kullanici Adiniz: 5329998877', $this->sentSmsMessages[0]['message']);
    }

    public function test_update_registration_shop_and_contact_name_after_password_change(): void
    {
        $agent = $this->createCallCenterAgent();

        $result = $this->service->register($agent, [
            'shop_name' => 'Eski Mağaza',
            'contact_name' => 'Eski Ad',
            'phone' => '5323334455',
            'email' => '',
        ]);

        $user = $result->user;
        $user->must_change_password = 0;
        $user->save();

        $this->assertTrue(
            $this->service->updateRegistration($result->vendor->fresh()->load('user'), [
                'shop_name' => 'Yeni Mağaza Adı',
                'contact_name' => 'Yeni Yetkili',
                'phone' => '5323334455',
                'send_sms' => false,
            ])
        );

        $vendor = $result->vendor->fresh();
        $user = $result->user->fresh();
        $this->assertSame('Yeni Mağaza Adı', $vendor->shop_name);
        $this->assertSame('Yeni Yetkili', $user->name);
        $this->assertSame('+905323334455', $user->phone);
    }

    public function test_resend_first_login_sms_blocked_after_password_change(): void
    {
        $agent = $this->createCallCenterAgent();

        $result = $this->service->register($agent, [
            'shop_name' => 'Blocked Mağaza',
            'contact_name' => 'Veli',
            'phone' => '5327778899',
            'email' => '',
        ]);

        $user = $result->user;
        $user->must_change_password = 0;
        $user->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Satıcı yeni şifresini oluşturmuş');

        $this->service->resendFirstLoginSms($result->vendor->fresh()->load('user'));
    }

    public function test_public_register_creates_seller_with_public_web_source(): void
    {
        $agent = Admin::query()->create([
            'name' => 'Agent',
            'email' => 'agent-public@example.com',
            'password' => bcrypt('secret'),
            'admin_type' => Admin::TYPE_CALL_CENTER,
            'status' => 1,
        ]);

        $result = $this->service->registerPublic([
            'shop_name' => 'Web Magaza',
            'contact_name' => 'Web Yetkili',
            'phone' => '5551112233',
            'email' => null,
        ]);

        $this->assertSame('public_web', $result->vendor->registration_source);
        $this->assertNull($result->vendor->registered_by_admin_id);
        $this->assertTrue($result->smsSent);
        $this->assertNotEmpty($result->otpCode);
    }

    public function test_public_register_stores_multiple_category_ids(): void
    {
        $result = $this->service->registerPublic([
            'shop_name' => 'Cok Kategorili Magaza',
            'contact_name' => 'Web Yetkili',
            'phone' => '5551112244',
            'category_ids' => [3, 7, 12],
        ]);

        $this->assertSame(3, (int) $result->vendor->primary_category_id);
        $this->assertSame([3, 7, 12], $result->vendor->registration_category_ids);
    }

    public function test_resend_first_login_sms_works_for_public_web(): void
    {
        $result = $this->service->registerPublic([
            'shop_name' => 'Web Sms Magaza',
            'contact_name' => 'Web Yetkili',
            'phone' => '5551112255',
        ]);

        $oldOtp = OtpVerification::where('phone', '+905551112255')->where('purpose', 'seller_first_login')->first();
        $this->assertNotNull($oldOtp);

        $this->sentSmsMessages = [];
        $this->smsCollector->messages = &$this->sentSmsMessages;

        $this->assertTrue($this->service->resendFirstLoginSms($result->vendor->fresh()->load('user')));
        $this->assertNotEmpty($this->sentSmsMessages);
        $this->assertSame($oldOtp->otp_code, OtpVerification::where('phone', '+905551112255')->where('purpose', 'seller_first_login')->value('otp_code'));
    }

    public function test_resend_first_login_sms_blocked_for_self_registration(): void
    {
        $result = $this->service->registerPublic([
            'shop_name' => 'Self Magaza',
            'contact_name' => 'Self Yetkili',
            'phone' => '5551112266',
        ]);

        $vendor = $result->vendor->fresh()->load('user');
        $vendor->registration_source = 'self';
        $vendor->save();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bu işlem sadece ilk giriş SMS kaydı olan satıcılar için geçerlidir.');

        $this->service->resendFirstLoginSms($vendor);
    }
}
