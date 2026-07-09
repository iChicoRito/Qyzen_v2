<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tbl_notifications MODIFY event_type ENUM(
            'assessment_created', 'assessment_updated', 'assessment_deleted', 'assessment_exempted',
            'learning_material_uploaded', 'learning_material_deleted',
            'quiz_created', 'quiz_uploaded', 'quiz_updated', 'quiz_deleted',
            'enrollment_created', 'enrollment_updated', 'enrollment_deleted',
            'retake_updated', 'quiz_submitted'
        ) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tbl_notifications MODIFY event_type ENUM(
            'assessment_created', 'assessment_updated', 'assessment_deleted',
            'learning_material_uploaded', 'learning_material_deleted',
            'quiz_created', 'quiz_uploaded', 'quiz_updated', 'quiz_deleted',
            'enrollment_created', 'enrollment_updated', 'enrollment_deleted',
            'retake_updated', 'quiz_submitted'
        ) NOT NULL");
    }
};
