<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_satisfaction_forms', function (Blueprint $table) {
            $table->string('customer_phone', 20)->nullable();
            $table->string('purchase_status', 30)->nullable()->index();
            $table->string('no_purchase_reason', 50)->nullable()->index();
            $table->unsignedTinyInteger('sales_response_score')->nullable();
            $table->json('support_positive_features')->nullable();
            $table->string('warranty_explained', 3)->nullable();
            $table->string('warranty_meets_needs', 3)->nullable();
            $table->unsignedTinyInteger('shipping_time_score')->nullable();
            $table->unsignedTinyInteger('packaging_quality_score')->nullable();
            $table->string('product_value_satisfied', 3)->nullable();
            $table->string('would_recommend', 3)->nullable();
            $table->string('would_choose_again', 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_satisfaction_forms', function (Blueprint $table) {
            $table->dropIndex(['purchase_status']);
            $table->dropIndex(['no_purchase_reason']);
            $table->dropColumn([
                'customer_phone', 'purchase_status', 'no_purchase_reason', 'sales_response_score',
                'support_positive_features', 'warranty_explained', 'warranty_meets_needs',
                'shipping_time_score', 'packaging_quality_score', 'product_value_satisfied',
                'would_recommend', 'would_choose_again',
            ]);
        });
    }
};
