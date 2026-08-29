<?php

namespace Tests\Feature\Seller;

use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class SellerKycApiTest extends TestCase
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
        Storage::disk('local')->deleteDirectory('private');
        Schema::dropIfExists('seller_kyc_documents');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_seller_can_upload_kyc_document_and_status_turns_pending(): void
    {
        $user = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'status' => 1,
            'shop_name' => 'Seller Shop',
        ]);

        $response = $this->actingAs($user, 'api')->post('/api/seller/kyc/upload', [
            'document_type' => 'tax_certificate',
            'document' => UploadedFile::fake()->create('vergi-levhasi.pdf', 100, 'application/pdf'),
            'iban' => 'TR000000000000000000000001',
            'tax_number' => '1234567890',
        ]);

        $response->assertCreated()
            ->assertJsonPath('document.document_type', 'tax_certificate')
            ->assertJsonPath('status.kyc_status', 'pending');

        $this->assertDatabaseHas('seller_kyc_documents', [
            'seller_id' => $vendor->id,
            'document_type' => 'tax_certificate',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'kyc_status' => 'pending',
            'iban' => 'TR000000000000000000000001',
            'tax_number' => '1234567890',
        ]);
    }

    public function test_admin_can_approve_kyc_document_and_vendor_becomes_approved(): void
    {
        $user = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
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
            'file_path' => 'private/kyc/' . $vendor->id . '/identity-front.pdf',
            'original_name' => 'identity-front.pdf',
            'file_size' => 1234,
            'status' => 'pending',
        ]);

        $admin = Admin::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $response = $this->actingAs($admin, 'admin-api')->put("/api/admin/kyc/{$document->id}/approve", [
            'admin_note' => 'Approved after review',
        ]);

        $response->assertOk()
            ->assertJsonPath('document.status', 'approved')
            ->assertJsonPath('status.kyc_status', 'approved');

        $this->assertDatabaseHas('seller_kyc_documents', [
            'id' => $document->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'kyc_status' => 'approved',
        ]);
    }
}
