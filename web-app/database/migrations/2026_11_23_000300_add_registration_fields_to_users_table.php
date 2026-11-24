<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Pendaftaran mandiri mahasiswa (FR-015 sampai FR-018). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('self_registered')->default(false)->after('must_change_password');
            $table->timestamp('approved_at')->nullable()->after('self_registered');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['self_registered', 'approved_at']);
        });
    }
};
