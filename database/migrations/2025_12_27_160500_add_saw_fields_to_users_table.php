<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nif', 20)->nullable()->unique()->after('email');
            $table->string('telemovel', 20)->nullable()->after('nif');
            $table->string('morada', 255)->nullable()->after('telemovel');

            // Legacy-compatible: admin|user
            $table->enum('perfil', ['admin', 'user'])->default('user')->after('morada');

            // Legacy statuses: pending|active|blocked|deleted
            $table->enum('estado', ['pending', 'active', 'blocked', 'deleted'])->default('pending')->after('perfil');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nif']);
            $table->dropColumn(['nif', 'telemovel', 'morada', 'perfil', 'estado']);
        });
    }
};
