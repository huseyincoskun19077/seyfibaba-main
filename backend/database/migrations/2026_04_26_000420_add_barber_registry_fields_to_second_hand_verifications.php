<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('second_hand_verifications', function (Blueprint $table) {
            $table->string('barber_registry_number', 80)->nullable()->after('tax_number')->index();

            $table->string('barber_document_path')->nullable()->after('tax_document_size');
            $table->string('barber_document_original_name')->nullable()->after('barber_document_path');
            $table->unsignedBigInteger('barber_document_size')->nullable()->after('barber_document_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('second_hand_verifications', function (Blueprint $table) {
            $table->dropColumn([
                'barber_registry_number',
                'barber_document_path',
                'barber_document_original_name',
                'barber_document_size',
            ]);
        });
    }
};

