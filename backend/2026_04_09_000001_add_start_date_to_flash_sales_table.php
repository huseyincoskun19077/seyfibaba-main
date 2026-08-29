<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStartDateToFlashSalesTable extends Migration
{
    public function up()
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            if (!Schema::hasColumn('flash_sales', 'start_date')) {
                $table->dateTime('start_date')->nullable();
            }
            if (!Schema::hasColumn('flash_sales', 'end_date')) {
                $table->dateTime('end_date')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('flash_sales', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
}