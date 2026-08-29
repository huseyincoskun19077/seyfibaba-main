<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contact_pages')) {
            return;
        }

        Schema::table('contact_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('contact_pages', 'whatsapp')) {
                $table->string('whatsapp', 30)->nullable()->after('phone');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('contact_pages')) {
            return;
        }

        Schema::table('contact_pages', function (Blueprint $table) {
            if (Schema::hasColumn('contact_pages', 'whatsapp')) {
                $table->dropColumn('whatsapp');
            }
        });
    }
};
