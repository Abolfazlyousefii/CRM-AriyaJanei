<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('product_key')->unique();
            $table->unsignedBigInteger('base_price')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('final_price')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_overrides');
    }
};
