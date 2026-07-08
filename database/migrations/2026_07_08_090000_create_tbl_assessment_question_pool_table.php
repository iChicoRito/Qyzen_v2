<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 51: the "eligible set" pivot — which bank questions (tbl_quizzes) an assessment may
// randomly draw from. Distinct from tbl_assessments.pool_size (the draw count N).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_assessment_question_pool', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('tbl_assessments')->cascadeOnDelete();
            $table->foreignId('quiz_id')->constrained('tbl_quizzes')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['assessment_id', 'quiz_id']);
            $table->index('assessment_id');
            $table->index('quiz_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_assessment_question_pool');
    }
};
