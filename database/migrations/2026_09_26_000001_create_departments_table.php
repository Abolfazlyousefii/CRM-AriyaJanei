<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            });
        }

        $now = now();
        foreach (['حسابداری', 'انبار', 'فروش', 'آیتی'] as $name) {
            DB::table('departments')->insertOrIgnore(['name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('department_id');
            });
        }

        Schema::dropIfExists('departments');
    }
};
