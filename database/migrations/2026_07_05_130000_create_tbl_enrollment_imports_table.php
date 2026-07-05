<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 39 (timeline): per-file status record for the educator bulk-enrollment upload, polled by
// the enrollment-index timeline panel. Leaner than tbl_user_imports — no chunks/failed-rows, since
// enrollment processing halts at the first invalid row (task 39 spec).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_enrollment_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiated_by_user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('upload_path');
            $table->string('status')->default('queued'); // queued | processing | completed | failed
            $table->text('error_message')->nullable();
            $table->unsignedInteger('created_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_enrollment_imports');
    }
};
