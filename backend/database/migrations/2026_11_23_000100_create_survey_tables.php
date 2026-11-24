<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Skema survei. Lihat docs/03_System_Design/ERD.md.
 * Catatan anonimitas: tabel responses/answers TIDAK memiliki user_id,
 * responses memakai UUID acak, dan hanya tanggal (tanpa jam) yang disimpan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->dateTime('open_at');
            $table->dateTime('close_at');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->string('code', 10);
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->smallInteger('sort_order')->default(0);
            $table->unique(['survey_id', 'code']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('factor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20);
            $table->text('text');
            $table->enum('type', ['likert', 'single_choice', 'yes_no', 'text']);
            $table->enum('ml_role', ['factor_item', 'feature', 'target', 'descriptive', 'open']);
            $table->boolean('is_required')->default(true);
            $table->smallInteger('sort_order')->default(0);
            $table->unique(['survey_id', 'code']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('value', 50);
            $table->smallInteger('sort_order')->default(0);
        });

        Schema::create('survey_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('submitted_date');
            $table->unique(['survey_id', 'user_id']);
        });

        Schema::create('responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('duration_seconds');
            $table->date('submitted_date');
            $table->boolean('is_valid')->default(true);
            $table->string('flag_reason', 100)->nullable();
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->foreignUuid('response_id')->constrained('responses')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->decimal('value_numeric', 5, 2)->nullable();
            $table->text('value_text')->nullable();
            $table->primary(['response_id', 'question_id']);
        });

        Schema::create('response_features', function (Blueprint $table) {
            $table->foreignUuid('response_id')->primary()->constrained('responses')->cascadeOnDelete();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->json('features');
            $table->tinyInteger('target');
            $table->tinyInteger('kep_umum')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('response_features');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('responses');
        Schema::dropIfExists('survey_submissions');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('factors');
        Schema::dropIfExists('surveys');
    }
};
