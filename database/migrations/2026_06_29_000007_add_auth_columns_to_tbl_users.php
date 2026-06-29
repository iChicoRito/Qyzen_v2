<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stage C: Laravel auth credentials live on tbl_users (identity binds by email).
// Nullable so imported users (Stage E) start with no password and are forced
// through password-reset on first login.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->string('password')->nullable()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('password');
            $table->rememberToken();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['password', 'email_verified_at', 'remember_token']);
        });
    }
};
