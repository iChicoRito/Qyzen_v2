<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: docs/architecture/LIVE_SCHEMA_EXPORT.sql — tbl_academic_year -> tbl_academic_term. (B4)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_academic_year', function (Blueprint $table) {
            $table->id();
            $table->string('year')->unique();   // format "YYYY - YYYY"
            $table->boolean('is_active')->default(true);
        });

        Schema::create('tbl_academic_term', function (Blueprint $table) {
            $table->id();
            $table->string('term_name');
            $table->enum('semester', ['1st Semester', '2nd Semester']);
            $table->foreignId('academic_year_id')->constrained('tbl_academic_year')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unique(['term_name', 'semester', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_academic_term');
        Schema::dropIfExists('tbl_academic_year');
    }
};
