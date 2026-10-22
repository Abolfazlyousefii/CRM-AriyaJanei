<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('org_position', 40)->nullable()->after('department_id');
        });

        Schema::table('departments', function (Blueprint $table): void {
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_manager_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('supervisor_id');
            $table->dropConstrainedForeignId('manager_id');
            $table->dropConstrainedForeignId('parent_manager_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('org_position');
        });
    }
};
