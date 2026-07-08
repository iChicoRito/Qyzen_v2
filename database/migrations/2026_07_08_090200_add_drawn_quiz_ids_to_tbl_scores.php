<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 51: the subset of bank question ids drawn for this specific attempt, pinned once at
// first saveDraft() and never re-rolled — a pool draw is a real selection (unlike is_shuffle,
// which only reorders an always-fully-shown set), so it must stay stable across resume/refresh.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_scores', function (Blueprint $table) {
            $table->json('drawn_quiz_ids')->nullable()->after('total_questions');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_scores', function (Blueprint $table) {
            $table->dropColumn('drawn_quiz_ids');
        });
    }
};
