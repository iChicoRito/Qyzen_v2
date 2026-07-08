<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: docs/architecture/LIVE_SCHEMA_EXPORT.sql — tbl_users. No password column (auth lived in Supabase; Stage C decides storage).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->enum('user_type', ['admin', 'student', 'educator']);
            $table->string('user_id')->unique();   // historical Supabase identity key — preserve on import
            $table->string('given_name');
            $table->string('surname');
            $table->string('email')->unique();
            $table->boolean('is_active')->default(true);
            $table->text('profile_picture')->nullable();
            $table->text('cover_photo')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index('user_id');
            $table->index('user_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_users');
    }
};
