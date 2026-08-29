<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('taksit_kategorileri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->boolean('taksit_olabilir')->default(1); // 1=taksitli, 0=tek cekim
            $table->integer('min_tutar')->default(5000); // Taksit siniri
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('taksit_kategorileri');
    }
};
