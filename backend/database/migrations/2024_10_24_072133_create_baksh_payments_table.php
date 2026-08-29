<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBakshPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('baksh_payments', function (Blueprint $table) {
            $table->id();
            $table->string('account_mode')->default(0);
            $table->string('username');
            $table->string('password');
            $table->string('app_key');
            $table->string('app_secret');
            $table->string('image')->nullable();
            $table->integer('status')->default(0);
            $table->integer('currency_id')->nullable();
            $table->timestamps();
        });

        if (!\Illuminate\Support\Facades\DB::table('baksh_payments')->exists()) {
            \Illuminate\Support\Facades\DB::table('baksh_payments')->insert([
                'account_mode' => '0',
                'username' => 'username',
                'password' => 'password@12345',
                'app_key' => 'app_key',
                'app_secret' => 'app_secret',
                'image' => 'image',
                'status' => '0',
                'currency_id' => '1',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('baksh_payments');
    }
}
