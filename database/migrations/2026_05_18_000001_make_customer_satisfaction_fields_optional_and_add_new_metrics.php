<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_satisfaction_forms', function (Blueprint $table) {
            $table->date('submitted_at')->nullable()->change();
            $table->string('customer_name')->nullable()->change();
            $table->string('customer_family')->nullable()->change();
            $table->enum('shipping_method', ['barbari', 'tipax', 'rahmati', 'ghafari', 'nadi', 'hozori'])->nullable()->change();
            $table->enum('satisfaction_status', ['satisfied', 'unsatisfied'])->nullable()->change();
            $table->foreignId('assigned_to_user_id')->nullable()->change();

            $table->unsignedTinyInteger('operator_communication_score')->nullable()->after('satisfaction_status');
            $table->unsignedTinyInteger('shipment_score')->nullable()->after('operator_communication_score');
            $table->unsignedTinyInteger('product_quality_score')->nullable()->after('shipment_score');
            $table->enum('needs_consultation', ['yes', 'no'])->nullable()->after('product_quality_score');
            $table->enum('wants_in_person_purchase', ['yes', 'no'])->nullable()->after('needs_consultation');
        });
    }

    public function down(): void
    {
        Schema::table('customer_satisfaction_forms', function (Blueprint $table) {
            $table->dropColumn([
                'operator_communication_score',
                'shipment_score',
                'product_quality_score',
                'needs_consultation',
                'wants_in_person_purchase',
            ]);

            $table->date('submitted_at')->nullable(false)->change();
            $table->string('customer_name')->nullable(false)->change();
            $table->string('customer_family')->nullable(false)->change();
            $table->enum('shipping_method', ['barbari', 'tipax', 'rahmati', 'ghafari', 'nadi', 'hozori'])->nullable(false)->change();
            $table->enum('satisfaction_status', ['satisfied', 'unsatisfied'])->nullable(false)->change();
            $table->foreignId('assigned_to_user_id')->nullable(false)->change();
        });
    }
};
