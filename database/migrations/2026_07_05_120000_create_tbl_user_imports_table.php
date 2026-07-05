<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_user_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('initiated_by_user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('upload_path');
            $table->string('failed_report_path')->nullable();
            $table->string('status')->default('queued');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('total_chunks')->default(0);
            $table->unsignedInteger('processed_chunks')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->json('failed_rows')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_imports');
    }
};
