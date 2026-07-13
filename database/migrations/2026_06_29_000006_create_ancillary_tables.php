<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: docs/architecture/LIVE_SCHEMA_EXPORT.sql — group chats/messages/reads, student presence,
// learning materials, notifications. (B9 + B10)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_group_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('tbl_subjects')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('tbl_sections')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['educator_id', 'subject_id', 'section_id']);
            $table->index('educator_id');
            $table->index('section_id');
            $table->index('subject_id');
        });

        Schema::create('tbl_group_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_chat_id')->constrained('tbl_group_chats')->cascadeOnDelete();
            $table->foreignId('sender_user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->text('content');   // non-empty CHECK enforced in Form Request
            $table->timestamps();
            $table->index(['group_chat_id', 'created_at']);
            $table->index(['sender_user_id', 'created_at']);
        });

        Schema::create('tbl_group_chat_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_chat_id')->constrained('tbl_group_chats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->timestamp('last_read_at');
            $table->timestamps();
            $table->unique(['group_chat_id', 'user_id']);
            $table->index(['group_chat_id', 'last_read_at']);
            $table->index('user_id');
        });

        Schema::create('tbl_student_presence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->timestamp('last_seen_at')->useCurrent();
            $table->text('current_path')->nullable();
            $table->timestamps();
            $table->unique('student_id');   // one presence row per student
            $table->index('last_seen_at');
        });

        Schema::create('tbl_learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('educator_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('tbl_subjects')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('tbl_sections')->cascadeOnDelete();
            $table->string('storage_bucket')->default('learning-materials');
            $table->string('storage_path');   // varchar (was text) so it can be indexed in MySQL
            $table->string('file_name');
            $table->string('file_extension');
            $table->string('mime_type');
            $table->bigInteger('file_size')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('educator_id');
            $table->index('section_id');
            $table->index(['storage_bucket', 'storage_path']);
            $table->index('subject_id');
            $table->index('updated_at');
        });

        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->enum('event_type', [
                'assessment_created', 'assessment_updated', 'assessment_deleted',
                'assessment_exempted', 'assessment_access_granted',
                'learning_material_uploaded', 'learning_material_deleted',
                'quiz_created', 'quiz_uploaded', 'quiz_updated', 'quiz_deleted',
                'enrollment_created', 'enrollment_updated', 'enrollment_deleted',
                'retake_updated', 'quiz_submitted', 'announcement_created', 'student_email_verified',
            ]);
            $table->string('title');
            $table->text('message');
            $table->text('link_path')->nullable();
            $table->foreignId('assessment_id')->nullable()->constrained('tbl_assessments')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('tbl_subjects')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('tbl_sections')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index('actor_user_id');
            $table->index('assessment_id');
            $table->index(['recipient_user_id', 'created_at'], 'idx_notif_recipient_created');
            $table->index(['recipient_user_id', 'is_read', 'created_at'], 'idx_notif_recipient_unread_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_notifications');
        Schema::dropIfExists('tbl_learning_materials');
        Schema::dropIfExists('tbl_student_presence');
        Schema::dropIfExists('tbl_group_chat_reads');
        Schema::dropIfExists('tbl_group_chat_messages');
        Schema::dropIfExists('tbl_group_chats');
    }
};
