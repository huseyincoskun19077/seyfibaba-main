<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'initial_qty')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('initial_qty')->nullable()->after('qty');
            });

            DB::table('products')->whereNull('initial_qty')->update([
                'initial_qty' => DB::raw('qty'),
            ]);
        }

        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                if (! Schema::hasColumn('settings', 'low_stock_relative_percent')) {
                    $table->integer('low_stock_relative_percent')->default(20)->after('low_stock_threshold');
                }
                if (! Schema::hasColumn('settings', 'low_stock_min_qty')) {
                    $table->integer('low_stock_min_qty')->default(1)->after('low_stock_relative_percent');
                }
                if (! Schema::hasColumn('settings', 'product_view_reminder_count')) {
                    $table->integer('product_view_reminder_count')->default(3)->after('low_stock_min_qty');
                }
                if (! Schema::hasColumn('settings', 'product_view_reminder_cooldown_days')) {
                    $table->integer('product_view_reminder_cooldown_days')->default(7)->after('product_view_reminder_count');
                }
            });
        }

        if (Schema::hasTable('campaigns') && ! Schema::hasColumn('campaigns', 'notified_at')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->timestamp('notified_at')->nullable()->after('status');
            });
        }

        if (! Schema::hasTable('user_product_views')) {
            Schema::create('user_product_views', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->unsignedInteger('view_count')->default(0);
                $table->timestamp('last_viewed_at')->nullable();
                $table->timestamp('reminded_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'product_id']);
                $table->index('view_count');
                $table->index('last_viewed_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_product_views');

        if (Schema::hasTable('campaigns') && Schema::hasColumn('campaigns', 'notified_at')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('notified_at');
            });
        }

        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table) {
                $columns = [];
                foreach (['product_view_reminder_cooldown_days', 'product_view_reminder_count', 'low_stock_min_qty', 'low_stock_relative_percent'] as $column) {
                    if (Schema::hasColumn('settings', $column)) {
                        $columns[] = $column;
                    }
                }
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'initial_qty')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('initial_qty');
            });
        }
    }
};
