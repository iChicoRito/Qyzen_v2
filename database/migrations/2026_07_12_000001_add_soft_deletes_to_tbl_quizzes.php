<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 13: soft-delete bank questions so removing one never touches historical student
// scores. Deleted questions drop out of the bank/pool (global scope) but stay resolvable
// for past-attempt review via withTrashed().
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_quizzes', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
