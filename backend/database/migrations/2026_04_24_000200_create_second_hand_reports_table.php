<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('second_hand_reports')) {
            return;
        }

        Schema::create('second_hand_reports', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('reporter_user_id')->index();
            $table->string('subject_type')->index(); // listing | message | user
            $table->unsignedBigInteger('subject_id')->nullable()->index();

            // Opsiyonel bağlam (UI ve moderasyon için)
            $table->unsignedBigInteger('listing_id')->nullable()->index();
            $table->unsignedBigInteger('conversation_id')->nullable()->index();
            $table->unsignedBigInteger('message_id')->nullable()->index();

            $table->string('reason')->index(); // spam | scam | harassment | illegal | other
            $table->text('details')->nullable();

            // open | reviewing | resolved | dismissed
            $table->string('status')->default('open')->index();

            $table->unsignedBigInteger('handled_by')->nullable()->index();
            $table->timestamp('handled_at')->nullable();

            $table->text('admin_note')->nullable();

            $table->timestamps();

            $table->foreign('reporter_user_id')->references('id')->on('users')->onDelete('cascade');

            // Aynı kullanıcı aynı konuyu spam etmesin (listing/message için)
            $table->unique(['reporter_user_id', 'subject_type', 'subject_id'], 'uniq_second_hand_report_subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_hand_reports');
    }
};
