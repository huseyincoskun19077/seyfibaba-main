<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stock alert settings
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(5)->after('id');
            }

            if (!Schema::hasColumn('settings', 'stock_alert_enabled')) {
                $table->boolean('stock_alert_enabled')->default(true)->after('low_stock_threshold');
            }
        });

        // Laravel-style notifications table
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::dropIfExists('notifications');
        }

        Schema::table('settings', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('settings', 'low_stock_threshold')) {
                $columns[] = 'low_stock_threshold';
            }

            if (Schema::hasColumn('settings', 'stock_alert_enabled')) {
                $columns[] = 'stock_alert_enabled';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
