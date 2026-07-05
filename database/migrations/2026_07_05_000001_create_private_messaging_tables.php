<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Task 30 — private 1:1 student/educator messaging. Separate from tbl_group_chats
// (untouched/inactive); one shared thread per student-educator pair, subject-agnostic.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'educator_id']);
            $table->index('educator_id');
            $table->index('student_id');
        });

        Schema::create('tbl_conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('tbl_conversations')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->text('content'); // blanked to '' when message_deleted_at is set
            $table->timestamp('edited_at')->nullable();
            $table->timestamp('message_deleted_at')->nullable(); // NOT Eloquent SoftDeletes — history stays, content is blanked
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender_user_id', 'created_at']);
        });

        Schema::create('tbl_conversation_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('tbl_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->timestamp('last_read_at');
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['conversation_id', 'last_read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_conversation_reads');
        Schema::dropIfExists('tbl_conversation_messages');
        Schema::dropIfExists('tbl_conversations');
    }
};
