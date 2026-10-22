<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_contacts', function (Blueprint $table) {
            // Historical visitor codes do not reliably identify a CRM user.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('follow_up_status', 30)->default('new')->index();
            $table->date('next_follow_up_at')->nullable()->index();
            $table->text('follow_up_note')->nullable();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('shop_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropIndex(['created_at']);
            $table->dropIndex(['follow_up_status']);
            $table->dropIndex(['next_follow_up_at']);
            $table->dropColumn(['follow_up_status', 'next_follow_up_at', 'follow_up_note']);
        });
    }
};
