<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class AdminSellerKycApiTest extends TestCase
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
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('status')->default(1);
            $table->string('shop_name')->nullable();
            $table->enum('kyc_status', ['not_submitted', 'pending', 'approved', 'rejected'])->default('not_submitted');
            $table->timestamp('kyc_submitted_at')->nullable();
            $table->timestamp('kyc_approved_at')->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('tax_number', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('seller_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->string('document_type');
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->unsignedInteger('file_size')->default(0);
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('seller_kyc_documents');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_admin_can_list_pending_sellers_and_reject_kyc_document(): void
    {
        $user = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'phone' => '+905551234567',
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'status' => 1,
            'shop_name' => 'Seller Shop',
            'kyc_status' => 'pending',
            'kyc_submitted_at' => now(),
        ]);

        $document = $vendor->kycDocuments()->create([
            'document_type' => 'identity_front',
            'file_path' => 'private/kyc/file.pdf',
            'original_name' => 'file.pdf',
            'file_size' => 1000,
            'status' => 'pending',
        ]);

        $admin = Admin::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $listResponse = $this->actingAs($admin, 'admin-api')->get('/api/admin/kyc/pending');
        $listResponse->assertOk()
            ->assertJsonPath('sellers.data.0.id', $vendor->id);

        $rejectResponse = $this->actingAs($admin, 'admin-api')->put("/api/admin/kyc/{$document->id}/reject", [
            'admin_note' => 'Document is not readable',
        ]);

        $rejectResponse->assertOk()
            ->assertJsonPath('document.status', 'rejected')
            ->assertJsonPath('status.kyc_status', 'rejected');

        $this->assertDatabaseHas('seller_kyc_documents', [
            'id' => $document->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
        ]);
    }
}
