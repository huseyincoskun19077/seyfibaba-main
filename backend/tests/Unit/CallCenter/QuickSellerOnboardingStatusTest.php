<?php

namespace Tests\Unit\CallCenter;

use App\Models\OtpVerification;
use App\Models\User;
use App\Models\Vendor;
use App\Services\CallCenter\QuickSellerOnboardingStatus;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class QuickSellerOnboardingStatusTest extends TestCase
{
    use UsesInMemorySqlite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configureInMemorySqlite();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('must_change_password')->default(true);
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
            $table->string('registration_source')->default('self');
            $table->boolean('welcome_sms_sent')->nullable();
            $table->timestamp('welcome_sms_sent_at')->nullable();
            $table->boolean('welcome_email_sent')->nullable();
            $table->timestamp('welcome_email_sent_at')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_shows_not_logged_in_when_sms_sent_but_otp_unverified(): void
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test',
            'email' => 'satici.532@pending.seyfibaba.local',
            'phone' => '+905321111111',
            'must_change_password' => true,
        ])->save();

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'shop_name' => 'Test',
            'registration_source' => 'call_center',
            'welcome_sms_sent' => true,
            'welcome_sms_sent_at' => now(),
        ]);
        $vendor->setRelation('user', $user);

        OtpVerification::query()->create([
            'phone' => '+905321111111',
            'otp_code' => '123456',
            'purpose' => 'seller_first_login',
            'expires_at' => Carbon::now()->addYear(),
            'verified_at' => null,
        ]);

        $status = QuickSellerOnboardingStatus::for($vendor);

        $this->assertTrue($status['applicable']);
        $this->assertTrue($status['sms_sent']);
        $this->assertFalse($status['logged_in']);
        $this->assertFalse($status['password_changed']);
        $this->assertSame('SMS gitti, sisteme girmedi', $status['summary']);
    }

    public function test_shows_password_pending_after_login(): void
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test',
            'email' => 'a@example.com',
            'phone' => '+905322222222',
            'must_change_password' => true,
        ])->save();

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'shop_name' => 'Test',
            'registration_source' => 'call_center',
            'welcome_sms_sent' => true,
            'welcome_email_sent' => true,
        ]);
        $vendor->setRelation('user', $user);

        OtpVerification::query()->create([
            'phone' => '+905322222222',
            'otp_code' => '654321',
            'purpose' => 'seller_first_login',
            'expires_at' => Carbon::now()->addYear(),
            'verified_at' => Carbon::now(),
        ]);

        $status = QuickSellerOnboardingStatus::for($vendor);

        $this->assertTrue($status['logged_in']);
        $this->assertFalse($status['password_changed']);
        $this->assertSame('Giriş yaptı, şifre oluşturmadı', $status['summary']);
    }

    public function test_shows_completed_when_password_changed(): void
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Test',
            'email' => 'b@example.com',
            'phone' => '+905323333333',
            'must_change_password' => false,
        ])->save();

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'shop_name' => 'Test',
            'registration_source' => 'call_center',
            'welcome_sms_sent' => true,
        ]);
        $vendor->setRelation('user', $user);

        OtpVerification::query()->create([
            'phone' => '+905323333333',
            'otp_code' => '111222',
            'purpose' => 'seller_first_login',
            'expires_at' => Carbon::now()->addYear(),
            'verified_at' => Carbon::now(),
        ]);

        $status = QuickSellerOnboardingStatus::for($vendor);

        $this->assertTrue($status['password_changed']);
        $this->assertSame('Şifre oluşturuldu', $status['summary']);
    }

    public function test_public_web_registration_can_resend_sms_but_cannot_edit_call_center_fields(): void
    {
        $user = new User();
        $user->forceFill([
            'name' => 'Web',
            'email' => 'web@example.com',
            'phone' => '+905324444444',
            'must_change_password' => true,
        ])->save();

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'shop_name' => 'Web Magaza',
            'registration_source' => 'public_web',
            'welcome_sms_sent' => false,
        ]);
        $vendor->setRelation('user', $user);

        $status = QuickSellerOnboardingStatus::for($vendor);

        $this->assertTrue($status['applicable']);
        $this->assertTrue($status['can_resend_sms']);
        $this->assertFalse($status['can_edit_phone']);
        $this->assertFalse($status['can_edit_registration']);
        $this->assertSame('SMS gitmedi, sisteme girmedi', $status['summary']);
    }
}
