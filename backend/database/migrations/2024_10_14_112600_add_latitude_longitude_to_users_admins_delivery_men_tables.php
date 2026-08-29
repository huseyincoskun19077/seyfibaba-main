<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLatitudeLongitudeToUsersAdminsDeliveryMenTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update users table
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });

        // Update admins table
        Schema::table('admins', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });

        // Update delivery_men table
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         // Drop columns in users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        // Drop columns in admins table
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        // Drop columns in delivery_men table
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
}
