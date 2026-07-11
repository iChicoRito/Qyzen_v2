<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_announcements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('tbl_subjects')->nullOnDelete();
            $table->boolean('is_global')->default(false);
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('body');
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['educator_id', 'created_at']);
            $table->index(['subject_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_announcements');
    }
};
