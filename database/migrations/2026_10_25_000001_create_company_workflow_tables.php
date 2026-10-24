<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_workflow_settings', function (Blueprint $t) {
            $t->unsignedBigInteger('id')->primary();
            $t->foreignId('internal_manager_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('executive_manager_id')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('leave_workflow_steps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('leave_id')->constrained('leaves')->restrictOnDelete();
            $t->unsignedSmallInteger('position');
            $t->string('stage', 20);
            $t->foreignId('approver_id')->constrained('users')->restrictOnDelete();
            $t->string('status', 20)->default('waiting');
            $t->timestamp('decided_at')->nullable();
            $t->timestamps();
            $t->unique(['leave_id', 'position']);
            $t->index(['approver_id', 'status']);
        });
        Schema::create('leave_workflow_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('leave_id')->constrained('leaves')->restrictOnDelete();
            $t->foreignId('step_id')->nullable()->constrained('leave_workflow_steps')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('principal_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('action', 30);
            $t->string('method', 20)->default('account');
            $t->text('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('leave_executive_codes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('leave_id')->unique()->constrained('leaves')->restrictOnDelete();
            $t->foreignId('step_id')->constrained('leave_workflow_steps')->restrictOnDelete();
            $t->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('executive_id')->constrained('users')->restrictOnDelete();
            $t->string('code_hash');
            $t->unsignedSmallInteger('attempts')->default(0);
            $t->string('delivery_status', 20)->default('sending');
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('report_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->text('body');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['report_comments', 'leave_executive_codes', 'leave_workflow_events', 'leave_workflow_steps', 'company_workflow_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
