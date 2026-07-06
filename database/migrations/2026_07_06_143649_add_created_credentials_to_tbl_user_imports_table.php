<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_user_imports', function (Blueprint $table) {
            $table->json('created_credentials')->nullable()->after('failed_rows');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_user_imports', function (Blueprint $table) {
            $table->dropColumn('created_credentials');
        });
    }
};
