<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReasonToProductReportsTable extends Migration
{
    public function up()
    {
        Schema::table('product_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('product_reports', 'reason')) {
                $table->string('reason')->nullable()->after('subject');
            }
        });
    }

    public function down()
    {
        Schema::table('product_reports', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
}