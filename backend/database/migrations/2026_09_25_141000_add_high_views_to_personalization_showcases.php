<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personalization_showcases')) {
            return;
        }

        Schema::table('personalization_showcases', function (Blueprint $table) {
            if (! Schema::hasColumn('personalization_showcases', 'include_high_views')) {
                $table->boolean('include_high_views')->default(false)->after('status');
            }
            if (! Schema::hasColumn('personalization_showcases', 'home_limit')) {
                $table->unsignedTinyInteger('home_limit')->default(12)->after('include_high_views');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('personalization_showcases')) {
            return;
        }

        Schema::table('personalization_showcases', function (Blueprint $table) {
            if (Schema::hasColumn('personalization_showcases', 'home_limit')) {
                $table->dropColumn('home_limit');
            }
            if (Schema::hasColumn('personalization_showcases', 'include_high_views')) {
                $table->dropColumn('include_high_views');
            }
        });
    }
};
