<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Legacy-compatible: store only the HMAC (sha256) of the 6-digit code.
            $table->string('token_validacao_email', 64)->nullable()->after('estado');
            $table->dateTime('token_validacao_email_expira')->nullable()->after('token_validacao_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['token_validacao_email', 'token_validacao_email_expira']);
        });
    }
};
