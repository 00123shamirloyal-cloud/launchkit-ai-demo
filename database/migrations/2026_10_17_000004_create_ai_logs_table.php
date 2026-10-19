<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('provider', 80);
            $table->string('model', 120)->nullable();
            $table->string('action', 100);
            $table->enum('status', ['succeeded', 'failed'])->default('succeeded');
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('cost', 12, 6)->default(0);
            $table->string('request_id', 128)->nullable()->index();
            $table->unsignedInteger('latency_ms')->nullable();
            // Must contain redacted metadata only; never store secrets or raw prompts.
            $table->json('metadata')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamps();

            $table->index(['provider', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_logs');
    }
};
