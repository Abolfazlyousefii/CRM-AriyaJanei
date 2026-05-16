<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (!Schema::hasColumn('leaves', 'leave_unit')) {
                $table->enum('leave_unit', ['روزانه', 'ساعتی'])
                    ->default('روزانه')
                    ->after('leave_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (Schema::hasColumn('leaves', 'leave_unit')) {
                $table->dropColumn('leave_unit');
            }
        });
    }
};