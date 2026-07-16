<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 24: special access was use-bounded only (one attempt after the window, never time-limited).
// Educators now pick a duration per grant; null keeps the original never-expires behavior, so
// existing rows need no backfill.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_student_assessment_access', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_student_assessment_access', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
