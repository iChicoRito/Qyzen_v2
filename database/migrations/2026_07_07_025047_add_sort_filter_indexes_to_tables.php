<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent — safe to re-run if partially applied.
        $add = function (string $table, string $column, string $name): void {
            try {
                Schema::table($table, fn (Blueprint $t) => $t->index($column, $name));
            } catch (Throwable $e) {
                if (! str_contains($e->getMessage(), 'Duplicate key')) {
                    throw $e;
                }
            }
        };

        $add('tbl_scores', 'student_id', 'idx_scores_student');
        $add('tbl_scores', 'educator_id', 'idx_scores_educator');
        $add('tbl_scores', 'assessment_id', 'idx_scores_assessment');
        $add('tbl_scores', 'subject_id', 'idx_scores_subject');
        $add('tbl_scores', 'section_id', 'idx_scores_section');
        $add('tbl_scores', 'is_passed', 'idx_scores_is_passed');
        $add('tbl_scores', 'submitted_at', 'idx_scores_submitted_at');

        $add('tbl_assessments', 'educator_id', 'idx_assessments_educator');
        $add('tbl_assessments', 'subject_id', 'idx_assessments_subject');
        $add('tbl_assessments', 'section_id', 'idx_assessments_section');
        $add('tbl_assessments', 'term', 'idx_assessments_term');
        $add('tbl_assessments', 'assessment_code', 'idx_assessments_code');
        $add('tbl_assessments', 'is_active', 'idx_assessments_is_active');

        $add('tbl_learning_materials', 'educator_id', 'idx_materials_educator');
        $add('tbl_learning_materials', 'subject_id', 'idx_materials_subject');
        $add('tbl_learning_materials', 'section_id', 'idx_materials_section');
        $add('tbl_learning_materials', 'is_active', 'idx_materials_is_active');

        $add('tbl_academic_term', 'academic_year_id', 'idx_term_year');
        $add('tbl_academic_term', 'is_active', 'idx_term_is_active');

        $add('tbl_subjects', 'educator_id', 'idx_subjects_educator');
        $add('tbl_subjects', 'sections_id', 'idx_subjects_section');

        $add('tbl_enrolled', 'student_id', 'idx_enrolled_student');
        $add('tbl_enrolled', 'educator_id', 'idx_enrolled_educator');
        $add('tbl_enrolled', 'subject_id', 'idx_enrolled_subject');
        $add('tbl_enrolled', 'is_active', 'idx_enrolled_is_active');
    }

    public function down(): void
    {
        $drop = function (string $table, string $name): void {
            try {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
            } catch (Throwable $e) {
                if (! str_contains($e->getMessage(), "Can't DROP")) {
                    throw $e;
                }
            }
        };

        foreach (['idx_scores_student', 'idx_scores_educator', 'idx_scores_assessment', 'idx_scores_subject', 'idx_scores_section', 'idx_scores_is_passed', 'idx_scores_submitted_at'] as $idx) {
            $drop('tbl_scores', $idx);
        }
        foreach (['idx_assessments_educator', 'idx_assessments_subject', 'idx_assessments_section', 'idx_assessments_term', 'idx_assessments_code', 'idx_assessments_is_active'] as $idx) {
            $drop('tbl_assessments', $idx);
        }
        foreach (['idx_materials_educator', 'idx_materials_subject', 'idx_materials_section', 'idx_materials_is_active'] as $idx) {
            $drop('tbl_learning_materials', $idx);
        }
        $drop('tbl_academic_term', 'idx_term_year');
        $drop('tbl_academic_term', 'idx_term_is_active');
        $drop('tbl_subjects', 'idx_subjects_educator');
        $drop('tbl_subjects', 'idx_subjects_section');
        foreach (['idx_enrolled_student', 'idx_enrolled_educator', 'idx_enrolled_subject', 'idx_enrolled_is_active'] as $idx) {
            $drop('tbl_enrolled', $idx);
        }
    }
};
