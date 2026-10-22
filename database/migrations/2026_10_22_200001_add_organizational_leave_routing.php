<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            // Null marks historical requests: keep their selected-approver flow.
            $table->string('approval_route', 24)->nullable();
            $table->foreignId('assigned_approver_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_approver_id');
            $table->dropColumn('approval_route');
        });
    }
};
