<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 51 follow-up: auto-labeled creation batch, so an educator can see which upload/add
// action a bank question came from (e.g. "Upload: quiz1.xlsx · Jul 8, 2026 2:30 PM").
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->string('batch_label')->nullable()->after('correct_answer');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->dropColumn('batch_label');
        });
    }
};
