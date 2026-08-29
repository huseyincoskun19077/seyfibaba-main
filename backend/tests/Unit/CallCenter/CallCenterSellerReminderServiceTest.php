<?php

namespace Tests\Unit\CallCenter;

use App\Models\OtpVerification;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CallCenter\CallCenterSellerReminderService;
use App\Services\SmsServiceInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class CallCenterSellerReminderServiceTest extends TestCase
{
    use UsesInMemorySqlite;

    protected CallCenterSellerReminderService $service;

    public array $sentSmsMessages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->nullable()->unique();
            $table->text('name')->nullable();
            $table->string('category', 32)->nullable();
            $table->text('subject')->nullable();
            $table->text('description')->nullable();
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
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('shop_name')->nullable();
            $table->string('phone')->nullable();
            $table->integer('status')->default(1);
            $table->string('registration_source')->default('self');
            $table->unsignedBigInteger('registered_by_admin_id')->nullable();
            $table->boolean('welcome_sms_sent')->nullable();
            $table->string('kyc_status')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id');
            $table->string('name')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        $testCase = $this;
        $this->app->bind(SmsServiceInterface::class, fn () => new class($testCase) implements SmsServiceInterface {
            public function __construct(private CallCenterSellerReminderServiceTest $testCase)
            {
            }

            public function send(string $phone, string $message): bool
            {
                return true;
            }

            public function sendTransactional(string $phone, string $message): bool
            {
                $this->testCase->sentSmsMessages[] = ['phone' => $phone, 'message' => $message];

                return true;
            }
        });

        SmsTemplate::query()->create([
            'slug' => CallCenterSellerReminderService::SLUG_LOGIN,
            'name' => 'Login reminder',
            'category' => 'seller_reminder',
            'description' => 'Merhaba {{contact_name}}, sifre {{password}}, tel {{login_phone}}, {{login_url}}',
        ]);

        SmsTemplate::query()->create([
            'slug' => CallCenterSellerReminderService::SLUG_KYC,
            'name' => 'KYC reminder',
            'category' => 'seller_reminder',
            'description' => 'KYC {{shop_name}} {{login_url}}',
        ]);

        $this->service = app(CallCenterSellerReminderService::class);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('users');
        Schema::dropIfExists('sms_templates');
        parent::tearDown();
    }

    protected function createVendor(array $overrides = []): Vendor
    {
        $user = new User();
        $user->name = $overrides['contact_name'] ?? 'Ali Veli';
        $user->email = 'satici.532@pending.seyfibaba.local';
        $user->phone = $overrides['phone'] ?? '+905321112233';
        $user->password = Hash::make('secret');
        $user->must_change_password = $overrides['must_change_password'] ?? 1;
        $user->status = 1;
        $user->save();

        $vendor = new Vendor();
        $vendor->user_id = $user->id;
        $vendor->shop_name = $overrides['shop_name'] ?? 'Test Magaza';
        $vendor->phone = $user->phone;
        $vendor->status = 1;
        $vendor->registration_source = 'call_center';
        $vendor->registered_by_admin_id = 1;
        $vendor->welcome_sms_sent = true;
        $vendor->kyc_status = $overrides['kyc_status'] ?? 'not_submitted';
        $vendor->save();

        if ($user->must_change_password) {
            OtpVerification::query()->create([
                'phone' => $user->phone,
                'otp_code' => '654321',
                'purpose' => 'seller_first_login',
                'attempts' => 0,
                'max_attempts' => 50,
                'expires_at' => now()->addYear(),
            ]);
        }

        return $vendor->fresh()->load('user');
    }

    public function test_offers_login_reminder_before_password_change(): void
    {
        $vendor = $this->createVendor();
        $options = $this->service->availableReminders($vendor);

        $this->assertCount(1, $options);
        $this->assertSame(CallCenterSellerReminderService::SLUG_LOGIN, $options[0]['slug']);
    }

    public function test_sends_login_reminder_with_template_variables(): void
    {
        $vendor = $this->createVendor();

        $this->assertTrue($this->service->send($vendor, CallCenterSellerReminderService::SLUG_LOGIN));
        $this->assertStringContainsString('Ali Veli', $this->sentSmsMessages[0]['message']);
        $this->assertStringContainsString('654321', $this->sentSmsMessages[0]['message']);
        $this->assertStringContainsString('5321112233', $this->sentSmsMessages[0]['message']);
        $this->assertStringContainsString('seyfibaba.com/satici-giris', $this->sentSmsMessages[0]['message']);
    }

    public function test_offers_kyc_reminder_after_password_change(): void
    {
        $vendor = $this->createVendor([
            'must_change_password' => 0,
            'kyc_status' => 'pending',
        ]);

        $options = $this->service->availableReminders($vendor);

        $this->assertCount(1, $options);
        $this->assertSame(CallCenterSellerReminderService::SLUG_KYC, $options[0]['slug']);
    }

    public function test_rejects_irrelevant_reminder(): void
    {
        $vendor = $this->createVendor([
            'must_change_password' => 0,
            'kyc_status' => 'approved',
        ]);

        $this->expectException(RuntimeException::class);
        $this->service->send($vendor, CallCenterSellerReminderService::SLUG_LOGIN);
    }
}
