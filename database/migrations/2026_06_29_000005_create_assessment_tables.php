<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: docs/architecture/LIVE_SCHEMA_EXPORT.sql — tbl_enrolled, tbl_assessments, tbl_quizzes,
// tbl_scores, tbl_student_assessment_retakes. (B6 + B7 + B8)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_enrolled', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('tbl_subjects')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['educator_id', 'student_id', 'subject_id']);
            $table->index('educator_id');
            $table->index('student_id');
            $table->index('subject_id');
        });

        Schema::create('tbl_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('tbl_subjects')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('tbl_sections')->cascadeOnDelete();
            $table->string('assessment_code');
            $table->string('time_limit');                 // text in source (e.g. minutes as string)
            $table->integer('cheating_attempts')->default(0);
            $table->boolean('is_shuffle')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
            $table->foreignId('term')->constrained('tbl_academic_term')->cascadeOnDelete();
            $table->boolean('allow_review')->default(false);
            $table->boolean('allow_hint')->default(false);
            $table->integer('hint_count')->default(0);
            $table->boolean('allow_retake')->default(false);
            $table->integer('retake_count')->default(0);
            $table->unique(['assessment_code', 'subject_id', 'section_id', 'term'], 'uq_assessments_code_subject_section_term');
            $table->index('assessment_code');
            $table->index('educator_id');
            $table->index('section_id');
            $table->index('start_date');
            $table->index('subject_id');
            $table->index('term');
        });

        Schema::create('tbl_quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('tbl_assessments')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('tbl_subjects')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('tbl_sections')->cascadeOnDelete();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->text('question');
            $table->enum('quiz_type', ['multiple_choice', 'identification']);
            $table->json('choices')->nullable();
            $table->text('correct_answer');               // hidden on the model; never serialized to a student
            $table->timestamps();
            $table->index('assessment_id');
            $table->index('educator_id');
            $table->index('quiz_type');
            $table->index('section_id');
            $table->index('subject_id');
        });

        Schema::create('tbl_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('tbl_assessments')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('tbl_subjects')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('tbl_sections')->cascadeOnDelete();
            $table->integer('score')->nullable();
            $table->integer('total_questions')->default(0);
            $table->json('student_answer');
            $table->integer('warning_attempts')->default(0);
            $table->enum('status', ['in_progress', 'submitted', 'passed', 'failed'])->default('in_progress');
            $table->boolean('is_passed')->default(false);
            $table->timestamp('taken_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index('assessment_id');
            $table->index('section_id');
            $table->index('status');
            $table->index(['student_id', 'assessment_id']);
            $table->index('student_id');
            $table->index('subject_id');
        });

        Schema::create('tbl_student_assessment_retakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('tbl_assessments')->cascadeOnDelete();
            $table->integer('extra_retake_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['educator_id', 'student_id', 'assessment_id'], 'uq_retakes_educator_student_assessment');
            $table->index('assessment_id');
            $table->index('educator_id');
            $table->index(['student_id', 'assessment_id'], 'idx_retakes_student_assessment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_student_assessment_retakes');
        Schema::dropIfExists('tbl_scores');
        Schema::dropIfExists('tbl_quizzes');
        Schema::dropIfExists('tbl_assessments');
        Schema::dropIfExists('tbl_enrolled');
    }
};
