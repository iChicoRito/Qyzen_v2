<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 01: enrollment import no longer halts at the first bad row — failures are collected and
// (like tbl_user_imports) exposed as a downloadable xlsx report.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_enrollment_imports', function (Blueprint $table) {
            $table->json('failed_rows')->nullable()->after('created_count');
            $table->string('failed_report_path')->nullable()->after('failed_rows');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_enrollment_imports', function (Blueprint $table) {
            $table->dropColumn(['failed_rows', 'failed_report_path']);
        });
    }
};
