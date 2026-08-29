<?php

namespace Tests\Feature\Seller;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class SellerBulkImportApiTest extends TestCase
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

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('sub_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('child_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sub_category_id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('logo')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_type')->nullable();
            $table->boolean('is_admin_created')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_id')->default(0);
            $table->string('short_name')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('thumb_image')->nullable();
            $table->unsignedBigInteger('category_id')->default(0);
            $table->unsignedBigInteger('sub_category_id')->default(0);
            $table->unsignedBigInteger('child_category_id')->default(0);
            $table->unsignedBigInteger('brand_id')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('offer_price', 10, 2)->nullable();
            $table->integer('qty')->default(0);
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->string('sku')->nullable();
            $table->string('weight')->nullable();
            $table->text('tags')->nullable();
            $table->integer('status')->default(1);
            $table->integer('is_undefine')->default(1);
            $table->integer('is_specification')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->integer('approve_by_admin')->default(0);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('openai_enabled')->default(false);
            $table->boolean('claude_enabled')->default(false);
            $table->timestamps();
        });
        \Illuminate\Support\Facades\DB::table('settings')->insert([
            'openai_enabled' => false,
            'claude_enabled' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('bulk_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->string('status')->default('pending');
            $table->text('error_log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Storage::disk('local')->deleteDirectory('private');

        Schema::dropIfExists('bulk_imports');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('child_categories');
        Schema::dropIfExists('sub_categories');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_seller_bulk_import_without_image_creates_draft_product(): void
    {
        $user = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'status' => 1,
            'shop_name' => 'Seller Shop',
            'kyc_status' => 'approved',
        ]);

        Category::query()->create(['name' => 'Kuaför Ekipmanları']);

        $csv = implode("\n", [
            'name,short_name,slug,category,sub_category,child_category,brand,price,offer_price,qty,short_description,long_description,sku,weight,tags,image_url',
            '"Profesyonel Erkek Berber Koltugu","Berber Koltugu","profesyonel-erkek-berber-koltugu","Kuaför Ekipmanları","","","","12500.00","10999.00","5","Hidrolik berber koltugu","Deri doseme, ayarlanabilir yukseklik","BK-001","45","berber koltugu,kuaför ekipmanlari",""',
        ]) . "\n";

        $response = $this->actingAs($user, 'api')->post('/api/seller/products/bulk-import', [
            'import_file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ]);

        $response->assertCreated()
            ->assertJsonPath('import.status', 'completed')
            ->assertJsonPath('import.success_count', 1);

        $this->assertDatabaseHas('products', [
            'vendor_id' => $vendor->id,
            'slug' => 'profesyonel-erkek-berber-koltugu',
            'status' => 0,
            'approve_by_admin' => 1,
        ]);
    }

    public function test_seller_bulk_import_template_endpoint_returns_csv(): void
    {
        $user = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
        ]);

        Vendor::query()->create([
            'user_id' => $user->id,
            'status' => 1,
            'shop_name' => 'Seller Shop',
            'kyc_status' => 'approved',
        ]);

        $response = $this->actingAs($user, 'api')->get('/api/seller/products/bulk-import/template');

        $response->assertOk();
        $this->assertStringContainsString('name,short_name,slug,category', $response->getContent());
        $this->assertStringContainsString('image_url', $response->getContent());
    }

    public function test_seller_bulk_import_client_format_without_category_creates_product_and_brand(): void
    {
        $user = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller2@example.com',
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'status' => 1,
            'shop_name' => 'Seller Shop 2',
            'kyc_status' => 'approved',
        ]);

        Category::query()->create(['name' => 'Kuaför Ekipmanları']);

        $csv = implode("\n", [
            'Kod,Barkod,Ürün Adı,Stok,Birim Fiyat,Marka,Resim Url',
            'P001,8690000000001,"Profesyonel Berber Makasi",10,"1.250,00","YeniMarkaTest",""',
        ]) . "\n";

        $response = $this->actingAs($user, 'api')->post('/api/seller/products/bulk-import', [
            'import_file' => UploadedFile::fake()->createWithContent('client-products.csv', $csv),
        ]);

        $response->assertCreated()
            ->assertJsonPath('import.status', 'completed')
            ->assertJsonPath('import.success_count', 1);

        $this->assertDatabaseHas('products', [
            'vendor_id' => $vendor->id,
            'name' => 'Profesyonel Berber Makasi',
            'status' => 0,
        ]);

        $this->assertDatabaseHas('brands', [
            'name' => 'YeniMarkaTest',
            'created_by' => $vendor->id,
            'created_by_type' => 'App\Models\Vendor',
        ]);
    }

    public function test_seller_bulk_import_stores_external_image_url_without_download(): void
    {
        $user = User::query()->create([
            'name' => 'Seller User',
            'email' => 'seller3@example.com',
        ]);

        $vendor = Vendor::query()->create([
            'user_id' => $user->id,
            'status' => 1,
            'shop_name' => 'Seller Shop 3',
            'kyc_status' => 'approved',
        ]);

        Category::query()->create(['name' => 'Kuaför Ekipmanları']);

        $cdnUrl = 'https://cdn.dsmcdn.com/ty1037/product/media/images/prod/SPM/PIM/20231102/00/937482ee-b476-310b-b294-ac65be29359d/1_org_zoom.jpg';

        $csv = implode("\n", [
            'Ürün Adı,Birim Fiyat,Stok,Marka,Resim Url',
            '"Profesyonel Berber Makasi",250.00,5,"Exodor","' . $cdnUrl . '"',
        ]) . "\n";

        $response = $this->actingAs($user, 'api')->post('/api/seller/products/bulk-import', [
            'import_file' => UploadedFile::fake()->createWithContent('cdn-products.csv', $csv),
        ]);

        $response->assertCreated()
            ->assertJsonPath('import.status', 'completed')
            ->assertJsonPath('import.success_count', 1);

        $this->assertDatabaseHas('products', [
            'vendor_id' => $vendor->id,
            'name' => 'Profesyonel Berber Makasi',
            'thumb_image' => $cdnUrl,
            'status' => 1,
        ]);
    }
}
