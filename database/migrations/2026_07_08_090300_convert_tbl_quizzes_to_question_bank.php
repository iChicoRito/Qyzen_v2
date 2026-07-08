<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Task 51: tbl_quizzes stops being a 1:1 child of one assessment and becomes the question
// bank itself — scoped by educator_id + subject_id only. Before dropping assessment_id/
// section_id, backfill every existing assessment's question set into the new eligibility
// pivot + pool_size, and pin every existing score's drawn_quiz_ids to that same set, so
// already-shipped assessments/attempts behave identically post-migration.
return new class extends Migration
{
    public function up(): void
    {
        // assessment_id => [quiz_id, ...], captured before the column is dropped.
        $questionsByAssessment = DB::table('tbl_quizzes')
            ->select('id', 'assessment_id')
            ->get()
            ->groupBy('assessment_id')
            ->map(fn ($rows) => $rows->pluck('id')->all());

        $now = now();
        foreach ($questionsByAssessment as $assessmentId => $quizIds) {
            DB::table('tbl_assessment_question_pool')->insert(array_map(fn ($quizId) => [
                'assessment_id' => $assessmentId,
                'quiz_id' => $quizId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $quizIds));

            DB::table('tbl_assessments')->where('id', $assessmentId)->update(['pool_size' => count($quizIds)]);
        }

        DB::table('tbl_scores')->orderBy('id')->chunkById(500, function ($scores) use ($questionsByAssessment) {
            foreach ($scores as $score) {
                $quizIds = $questionsByAssessment->get($score->assessment_id, []);
                DB::table('tbl_scores')->where('id', $score->id)->update([
                    'drawn_quiz_ids' => json_encode(array_values($quizIds)),
                ]);
            }
        });

        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->dropForeign(['assessment_id']);
            $table->dropForeign(['section_id']);
            $table->dropIndex(['assessment_id']);
            $table->dropIndex(['section_id']);
        });
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->dropColumn(['assessment_id', 'section_id']);
        });
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->index(['educator_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->dropIndex(['educator_id', 'subject_id']);
        });
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->foreignId('assessment_id')->nullable()->after('id')->constrained('tbl_assessments')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->after('subject_id')->constrained('tbl_sections')->cascadeOnDelete();
        });
    }
};
