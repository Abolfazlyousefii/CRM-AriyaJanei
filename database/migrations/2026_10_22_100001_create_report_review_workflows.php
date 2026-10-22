<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_review_workflows', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_id')->unique()->constrained('reports')->cascadeOnDelete();
            $table->string('status', 24)->default('pending');
            $table->foreignId('current_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('current_sequence')->default(1);
            $table->timestamps();
            $table->index(['status', 'current_reviewer_id']);
        });

        Schema::create('report_review_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('report_review_workflows')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_name', 255); // snapshot in case of user rename
            $table->string('decision', 24)->default('waiting');
            $table->text('comment')->nullable();
            $table->string('returned_description_hash', 64)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['workflow_id', 'sequence']);
            $table->index(['reviewer_id', 'decision']);
        });

        Schema::create('report_review_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_id')->constrained('report_review_workflows')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->unsignedSmallInteger('sequence');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['workflow_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_review_events');
        Schema::dropIfExists('report_review_steps');
        Schema::dropIfExists('report_review_workflows');
    }
};
