<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('category');
            $table->text('symptoms');
            $table->text('keywords')->nullable();
            $table->text('problem_description');
            $table->json('solution_steps');
            $table->text('expected_result');
            $table->string('status')->default('Draft')->index();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'category']);
        });

        Schema::create('troubleshooting_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category');
            $table->text('description');
            $table->string('source');
            $table->foreignId('selected_article_id')->nullable()->constrained('knowledge_base_articles')->nullOnDelete();
            $table->json('result_payload');
            $table->boolean('helpful')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('division');
            $table->string('issue_title');
            $table->text('description');
            $table->string('category');
            $table->string('device_code')->nullable();
            $table->string('priority')->default('Medium');
            $table->string('status')->default('Open')->index();
            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kb_article_id')->nullable()->constrained('knowledge_base_articles')->nullOnDelete();
            $table->foreignId('troubleshooting_result_id')->nullable()->constrained('troubleshooting_results')->nullOnDelete();
            $table->json('troubleshooting_history')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
            $table->index(['category', 'status', 'priority']);
        });

        Schema::create('ticket_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->text('note')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->timestamps();
            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('ticket_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_activities');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('troubleshooting_results');
        Schema::dropIfExists('knowledge_base_articles');
    }
};
