<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 01: per-attempt count of hints revealed, capped against tbl_assessments.hint_count.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_scores', function (Blueprint $table) {
            $table->unsignedTinyInteger('hints_used')->default(0)->after('warning_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_scores', function (Blueprint $table) {
            $table->dropColumn('hints_used');
        });
    }
};
