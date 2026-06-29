<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: docs/LiveSchemaExport.sql — tbl_sections -> tbl_sections_term -> tbl_subjects. (B5)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained('tbl_academic_term')->restrictOnDelete();
            $table->string('section_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('educator_id');
            $table->index('academic_term_id');
            $table->index('section_name');
        });

        Schema::create('tbl_sections_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('tbl_sections')->cascadeOnDelete();
            $table->foreignId('academic_term_id')->constrained('tbl_academic_term')->cascadeOnDelete();
            $table->unique(['section_id', 'academic_term_id']);
            $table->index('academic_term_id');
            $table->index('section_id');
        });

        Schema::create('tbl_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('sections_id')->constrained('tbl_sections')->cascadeOnDelete();
            $table->string('subject_code');
            $table->string('subject_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['educator_id', 'sections_id', 'subject_code']);
            $table->unique(['educator_id', 'sections_id', 'subject_name']);
            $table->index('educator_id');
            $table->index('sections_id');
            $table->index('subject_code');
            $table->index('subject_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_subjects');
        Schema::dropIfExists('tbl_sections_term');
        Schema::dropIfExists('tbl_sections');
    }
};
