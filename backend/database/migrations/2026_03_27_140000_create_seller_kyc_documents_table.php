<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->enum('document_type', [
                'identity_front',
                'identity_back',
                'tax_certificate',
                'address_proof',
                'bank_statement',
                'iban_document',
            ]);
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->unsignedInteger('file_size')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['seller_id', 'status'], 'seller_kyc_documents_seller_status_idx');
            $table->unique(['seller_id', 'document_type'], 'seller_kyc_documents_seller_type_unique');
        });

        Schema::table('vendors', function (Blueprint $table) {
            if (! Schema::hasColumn('vendors', 'kyc_status')) {
                $table->enum('kyc_status', ['not_submitted', 'pending', 'approved', 'rejected'])
                    ->default('not_submitted')
                    ->after('is_verified');
            }

            if (! Schema::hasColumn('vendors', 'kyc_submitted_at')) {
                $table->timestamp('kyc_submitted_at')->nullable()->after('kyc_status');
            }

            if (! Schema::hasColumn('vendors', 'kyc_approved_at')) {
                $table->timestamp('kyc_approved_at')->nullable()->after('kyc_submitted_at');
            }

            if (! Schema::hasColumn('vendors', 'iban')) {
                $table->string('iban', 34)->nullable()->after('kyc_approved_at');
            }

            if (! Schema::hasColumn('vendors', 'tax_number')) {
                $table->string('tax_number', 20)->nullable()->after('iban');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $columns = [];

            foreach (['kyc_status', 'kyc_submitted_at', 'kyc_approved_at', 'iban', 'tax_number'] as $column) {
                if (Schema::hasColumn('vendors', $column)) {
                    $columns[] = $column;
                }
            }

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::dropIfExists('seller_kyc_documents');
    }
};
