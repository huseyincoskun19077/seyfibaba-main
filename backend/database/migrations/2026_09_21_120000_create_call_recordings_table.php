<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_recordings', function (Blueprint $table) {
            $table->id();
            $table->string('uniqueid', 120)->unique();
            $table->string('common_id', 120)->nullable()->index();
            $table->string('source', 40)->nullable()->index();
            $table->string('destination', 80)->nullable()->index();
            $table->unsignedTinyInteger('direction')->nullable()->comment('0 inbound-ish / 1 outbound-ish per Netgsm');
            $table->unsignedInteger('duration_sec')->nullable();
            $table->timestamp('called_at')->nullable()->index();
            $table->string('line', 40)->nullable();
            $table->string('directory', 191)->nullable();
            $table->text('remote_recording_url')->nullable();
            $table->string('local_path', 255)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('sync_status', 30)->default('pending')->index();
            $table->text('sync_error')->nullable();
            $table->longText('transcript_text')->nullable();
            $table->string('transcript_status', 30)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_recordings');
    }
};
