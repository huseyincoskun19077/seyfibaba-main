<?php

namespace Tests\Feature\SecondHand;

use App\Models\SecondHandConversation;
use App\Models\SecondHandListing;
use App\Models\SecondHandMessage;
use App\Models\SecondHandReport;
use App\Models\SecondHandVerification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\UsesInMemorySqlite;
use Tests\TestCase;

class SecondHandSecurityApiTest extends TestCase
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

        Schema::create('second_hand_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('business_name');
            $table->string('tax_number')->index();
            $table->string('tax_document_path')->nullable();
            $table->string('tax_document_original_name')->nullable();
            $table->unsignedBigInteger('tax_document_size')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('second_hand_listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('sub_category_id')->nullable()->index();
            $table->unsignedBigInteger('child_category_id')->nullable()->index();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedBigInteger('city_id')->nullable()->index();
            $table->string('district')->nullable()->index();
            $table->string('condition')->default('used')->index();
            $table->string('status')->default('draft')->index();
            $table->string('inactive_reason')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
        });

        Schema::create('second_hand_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('listing_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedBigInteger('buyer_id')->index();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['listing_id', 'buyer_id']);
        });

        Schema::create('second_hand_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('sender_id')->index();
            $table->text('body');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('second_hand_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id')->index();
            $table->string('kind', 20)->default('file')->index();
            $table->string('path', 500);
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        Schema::create('second_hand_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_user_id')->index();
            $table->string('subject_type')->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->unsignedBigInteger('listing_id')->nullable()->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->string('reason')->index();
            $table->text('details')->nullable();
            $table->string('status')->default('open')->index();
            $table->unsignedBigInteger('handled_by')->nullable()->index();
            $table->timestamp('handled_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
            $table->unique(['reporter_user_id', 'subject_type', 'subject_id'], 'uniq_second_hand_report_subject');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('second_hand_reports');
        Schema::dropIfExists('second_hand_message_attachments');
        Schema::dropIfExists('second_hand_messages');
        Schema::dropIfExists('second_hand_conversations');
        Schema::dropIfExists('second_hand_listings');
        Schema::dropIfExists('second_hand_verifications');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    private function approveSecondHand(User $user): SecondHandVerification
    {
        return SecondHandVerification::query()->create([
            'user_id' => $user->id,
            'business_name' => 'Test İşletme',
            'tax_number' => '1234567890',
            'status' => SecondHandVerification::STATUS_APPROVED,
            'submitted_at' => now(),
        ]);
    }

    private function makeListing(User $seller, string $status): SecondHandListing
    {
        return SecondHandListing::query()->create([
            'user_id' => $seller->id,
            'title' => 'Test ilan',
            'description' => 'Açıklama',
            'price' => 100,
            'condition' => SecondHandListing::CONDITION_USED,
            'status' => $status,
            'published_at' => $status === SecondHandListing::STATUS_ACTIVE ? now() : null,
        ]);
    }

    public function test_unverified_user_cannot_access_second_hand_inbox(): void
    {
        $user = User::query()->create([
            'name' => 'Buyer',
            'email' => 'buyer@example.com',
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/user/second-hand/messages/inbox');

        $response->assertForbidden();
    }

    public function test_unverified_buyer_cannot_message_active_listing(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);
        $this->approveSecondHand($seller);

        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'buyer@example.com']);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_ACTIVE);

        $response = $this->actingAs($buyer, 'api')->postJson(
            "/api/user/second-hand/messages/listings/{$listing->id}",
            ['body' => 'Merhaba']
        );

        $response->assertForbidden();
    }

    public function test_buyer_cannot_message_when_seller_not_verified(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);

        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'buyer@example.com']);
        $this->approveSecondHand($buyer);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_ACTIVE);

        $response = $this->actingAs($buyer, 'api')->postJson(
            "/api/user/second-hand/messages/listings/{$listing->id}",
            ['body' => 'Merhaba']
        );

        $response->assertForbidden();
    }

    public function test_seller_cannot_message_own_listing(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);
        $this->approveSecondHand($seller);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_ACTIVE);

        $response = $this->actingAs($seller, 'api')->postJson(
            "/api/user/second-hand/messages/listings/{$listing->id}",
            ['body' => 'Kendi kendime']
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Kendi ilanınıza mesaj gönderemezsiniz.');
    }

    public function test_buyer_cannot_open_thread_on_non_active_listing(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);
        $this->approveSecondHand($seller);

        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'buyer@example.com']);
        $this->approveSecondHand($buyer);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_INACTIVE);

        $response = $this->actingAs($buyer, 'api')->postJson(
            "/api/user/second-hand/messages/listings/{$listing->id}",
            ['body' => 'Merhaba']
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Bu ilan için mesajlaşma kapalı.');
    }

    public function test_participant_cannot_send_when_listing_no_longer_active(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);
        $this->approveSecondHand($seller);

        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'buyer@example.com']);
        $this->approveSecondHand($buyer);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_ACTIVE);

        $conversation = SecondHandConversation::query()->create([
            'listing_id' => $listing->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'last_message_at' => now(),
        ]);

        $listing->update(['status' => SecondHandListing::STATUS_INACTIVE]);

        $response = $this->actingAs($buyer, 'api')->postJson(
            "/api/user/second-hand/messages/conversations/{$conversation->id}",
            ['body' => 'İlan kapalıyken mesaj']
        );

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Bu ilan için mesajlaşma kapalı.');
    }

    public function test_non_participant_cannot_read_conversation_messages(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);
        $this->approveSecondHand($seller);

        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'buyer@example.com']);
        $this->approveSecondHand($buyer);

        $stranger = User::query()->create(['name' => 'Stranger', 'email' => 'x@example.com']);
        $this->approveSecondHand($stranger);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_ACTIVE);

        $conversation = SecondHandConversation::query()->create([
            'listing_id' => $listing->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($stranger, 'api')->getJson(
            "/api/user/second-hand/messages/conversations/{$conversation->id}"
        );

        $response->assertForbidden();
    }

    public function test_opening_conversation_marks_other_party_messages_as_read(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);
        $this->approveSecondHand($seller);

        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'buyer@example.com']);
        $this->approveSecondHand($buyer);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_ACTIVE);

        $conversation = SecondHandConversation::query()->create([
            'listing_id' => $listing->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'last_message_at' => now(),
        ]);

        $incoming = SecondHandMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $seller->id,
            'body' => 'Satıcıdan cevap',
            'read_at' => null,
        ]);

        $this->actingAs($buyer, 'api')->getJson(
            "/api/user/second-hand/messages/conversations/{$conversation->id}"
        )->assertOk();

        $incoming->refresh();
        $this->assertNotNull($incoming->read_at);
    }

    public function test_duplicate_listing_report_returns_conflict(): void
    {
        $reporter = User::query()->create(['name' => 'R', 'email' => 'r@example.com']);
        $this->approveSecondHand($reporter);

        $owner = User::query()->create(['name' => 'O', 'email' => 'o@example.com']);
        $this->approveSecondHand($owner);

        $listing = $this->makeListing($owner, SecondHandListing::STATUS_ACTIVE);

        $payload = [
            'subject_type' => SecondHandReport::SUBJECT_LISTING,
            'subject_id' => $listing->id,
            'reason' => SecondHandReport::REASON_SPAM,
        ];

        $this->actingAs($reporter, 'api')
            ->postJson('/api/user/second-hand/reports', $payload)
            ->assertCreated();

        $this->actingAs($reporter, 'api')
            ->postJson('/api/user/second-hand/reports', $payload)
            ->assertStatus(409)
            ->assertJsonPath('message', 'Bu konu için zaten bir rapor göndermişsiniz.');
    }

    public function test_user_cannot_report_message_outside_their_conversation(): void
    {
        $seller = User::query()->create(['name' => 'Seller', 'email' => 'seller@example.com']);
        $this->approveSecondHand($seller);

        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'buyer@example.com']);
        $this->approveSecondHand($buyer);

        $stranger = User::query()->create(['name' => 'Stranger', 'email' => 's@example.com']);
        $this->approveSecondHand($stranger);

        $listing = $this->makeListing($seller, SecondHandListing::STATUS_ACTIVE);

        $conversation = SecondHandConversation::query()->create([
            'listing_id' => $listing->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'last_message_at' => now(),
        ]);

        $message = SecondHandMessage::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $buyer->id,
            'body' => 'Mesaj',
        ]);

        $response = $this->actingAs($stranger, 'api')->postJson('/api/user/second-hand/reports', [
            'subject_type' => SecondHandReport::SUBJECT_MESSAGE,
            'subject_id' => $message->id,
            'reason' => SecondHandReport::REASON_HARASSMENT,
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_report_themselves(): void
    {
        $user = User::query()->create(['name' => 'U', 'email' => 'u@example.com']);
        $this->approveSecondHand($user);

        $response = $this->actingAs($user, 'api')->postJson('/api/user/second-hand/reports', [
            'subject_type' => SecondHandReport::SUBJECT_USER,
            'subject_id' => $user->id,
            'reason' => SecondHandReport::REASON_OTHER,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Kendinizi raporlayamazsınız.');
    }
}
