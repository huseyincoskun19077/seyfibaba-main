<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('logo');
            $table->string('created_by_type')->nullable()->after('created_by');
            $table->boolean('is_admin_created')->default(false)->after('created_by_type');
        });
    }

    public function down()
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'created_by_type', 'is_admin_created']);
        });
    }
};