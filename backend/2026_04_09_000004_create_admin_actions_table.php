<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminActionsTable extends Migration
{
    public function up()
    {
        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->integer('admin_id');
            $table->string('action_type');
            $table->string('model_type');
            $table->integer('model_id')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('admin_id');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_actions');
    }
}