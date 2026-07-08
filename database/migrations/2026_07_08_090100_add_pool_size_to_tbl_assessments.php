<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 51: N — how many questions to randomly draw from the eligible pool per attempt.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_assessments', function (Blueprint $table) {
            $table->unsignedInteger('pool_size')->default(0)->after('is_shuffle');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_assessments', function (Blueprint $table) {
            $table->dropColumn('pool_size');
        });
    }
};
