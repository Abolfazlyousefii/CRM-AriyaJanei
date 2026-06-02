<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category_value')->nullable()->index();
            $table->string('category_name')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('base_price')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('final_price')->default(0);
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_products');
    }
};
