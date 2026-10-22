<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_contacts', function (Blueprint $table) {
            $table->string('buyer_name', 120)->nullable();
            $table->string('customer_type', 32)->nullable()->index();
            $table->json('requested_products')->nullable();
            $table->string('requested_products_note', 255)->nullable();
            $table->string('purchase_volume', 32)->nullable()->index();
            $table->string('purchase_cycle', 32)->nullable();
            $table->string('preferred_payment', 32)->nullable();
            $table->json('purchase_priorities')->nullable();
            $table->string('visit_result', 32)->nullable()->index();
            $table->string('next_action', 32)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('shop_contacts', function (Blueprint $table) {
            $table->dropColumn(['buyer_name', 'customer_type', 'requested_products', 'requested_products_note', 'purchase_volume', 'purchase_cycle', 'preferred_payment', 'purchase_priorities', 'visit_result', 'next_action']);
        });
    }
};
