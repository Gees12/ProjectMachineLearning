<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('survey_id')->constrained()->cascadeOnDelete();
            $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('model_version', 30);
            $table->string('algorithm', 50);
            $table->json('metrics');
            $table->unsignedInteger('n_responses')->default(0);
            $table->enum('status', ['success', 'failed']);
            $table->text('error_message')->nullable();
            $table->dateTime('run_at');
        });

        Schema::create('analysis_predictions', function (Blueprint $table) {
            $table->foreignId('analysis_run_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('response_id')->constrained('responses')->cascadeOnDelete();
            $table->decimal('probability', 5, 4);
            $table->enum('risk_level', ['rendah', 'sedang', 'tinggi']);
            $table->primary(['analysis_run_id', 'response_id']);
        });

        Schema::create('analysis_shap', function (Blueprint $table) {
            $table->foreignId('analysis_run_id')->constrained()->cascadeOnDelete();
            $table->string('feature_code', 30);
            $table->decimal('mean_abs_shap', 8, 5);
            $table->enum('direction', ['negatif', 'positif', 'campuran']);
            $table->smallInteger('rank');
            $table->decimal('top3_frequency', 4, 3)->nullable();
            $table->primary(['analysis_run_id', 'feature_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_shap');
        Schema::dropIfExists('analysis_predictions');
        Schema::dropIfExists('analysis_runs');
    }
};
