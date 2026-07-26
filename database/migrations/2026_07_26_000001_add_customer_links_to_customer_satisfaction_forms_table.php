<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_satisfaction_forms', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_seller_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_satisfaction_forms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_seller_user_id');
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
