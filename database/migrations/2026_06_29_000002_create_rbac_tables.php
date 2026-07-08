<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Source: docs/architecture/LIVE_SCHEMA_EXPORT.sql — tbl_roles, tbl_permissions, tbl_role_permissions, tbl_user_roles. (B2 + B3)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   // CHECK ^[a-z]+(_[a-z]+)*$ enforced in Form Request
            $table->text('description');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('tbl_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('resource');
            $table->string('action');
            $table->text('description');
            $table->string('module');
            $table->boolean('is_active')->default(true);
            $table->string('permission_string')->nullable()->unique();
        });

        Schema::create('tbl_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('tbl_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('tbl_permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('tbl_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('tbl_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'role_id']);
            $table->index('role_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_user_roles');
        Schema::dropIfExists('tbl_role_permissions');
        Schema::dropIfExists('tbl_permissions');
        Schema::dropIfExists('tbl_roles');
    }
};
