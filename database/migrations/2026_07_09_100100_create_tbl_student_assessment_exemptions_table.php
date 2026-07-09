<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 01: educator marks a student unable to take a specific assessment (e.g. absent). Same
// shape as tbl_student_assessment_retakes — a per-student-per-assessment override table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_student_assessment_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained('tbl_assessments')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['educator_id', 'student_id', 'assessment_id'], 'uq_exemptions_educator_student_assessment');
            $table->index('assessment_id');
            $table->index(['student_id', 'assessment_id'], 'idx_exemptions_student_assessment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_student_assessment_exemptions');
    }
};
