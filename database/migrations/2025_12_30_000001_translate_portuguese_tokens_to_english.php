<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // If the project was installed with legacy MySQL ENUMs in Portuguese,
        // expand them temporarily to accept both Portuguese and English values.
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                DB::statement("ALTER TABLE `users` MODIFY `perfil` ENUM('admin','utente','user') NOT NULL DEFAULT 'utente'");
                DB::statement("ALTER TABLE `users` MODIFY `estado` ENUM('pendente','disponivel','bloqueado','eliminado','pending','active','blocked','deleted') NOT NULL DEFAULT 'pendente'");

                DB::statement("ALTER TABLE `salas` MODIFY `estado` ENUM('disponivel','indisponivel','brevemente','available','unavailable','coming_soon') NOT NULL DEFAULT 'disponivel'");
                DB::statement("ALTER TABLE `salas` MODIFY `estado_registo` ENUM('disponivel','bloqueado','eliminado','active','blocked','deleted') NOT NULL DEFAULT 'disponivel'");
            } catch (Throwable $e) {
                // Best-effort only; if tables/columns are already migrated or not ENUMs, ignore.
            }
        }

        // Users
        DB::table('users')
            ->where('perfil', 'utente')
            ->update(['perfil' => 'user']);

        DB::table('users')
            ->where('perfil', 'cliente')
            ->update(['perfil' => 'user']);

        DB::table('users')
            ->where('estado', 'pendente')
            ->update(['estado' => 'pending']);

        DB::table('users')
            ->where('estado', 'disponivel')
            ->update(['estado' => 'active']);

        DB::table('users')
            ->where('estado', 'bloqueado')
            ->update(['estado' => 'blocked']);

        DB::table('users')
            ->where('estado', 'eliminado')
            ->update(['estado' => 'deleted']);

        // Rooms (salas)
        DB::table('salas')
            ->where('estado', 'disponivel')
            ->update(['estado' => 'available']);

        DB::table('salas')
            ->where('estado', 'indisponivel')
            ->update(['estado' => 'unavailable']);

        DB::table('salas')
            ->where('estado', 'brevemente')
            ->update(['estado' => 'coming_soon']);

        DB::table('salas')
            ->where('estado_registo', 'disponivel')
            ->update(['estado_registo' => 'active']);

        DB::table('salas')
            ->where('estado_registo', 'bloqueado')
            ->update(['estado_registo' => 'blocked']);

        DB::table('salas')
            ->where('estado_registo', 'eliminado')
            ->update(['estado_registo' => 'deleted']);
    }

    public function down(): void
    {
        // Best-effort rollback back to Portuguese tokens.

        DB::table('users')
            ->where('perfil', 'user')
            ->update(['perfil' => 'utente']);

        DB::table('users')
            ->where('estado', 'pending')
            ->update(['estado' => 'pendente']);

        DB::table('users')
            ->where('estado', 'active')
            ->update(['estado' => 'disponivel']);

        DB::table('users')
            ->where('estado', 'blocked')
            ->update(['estado' => 'bloqueado']);

        DB::table('users')
            ->where('estado', 'deleted')
            ->update(['estado' => 'eliminado']);

        DB::table('salas')
            ->where('estado', 'available')
            ->update(['estado' => 'disponivel']);

        DB::table('salas')
            ->where('estado', 'unavailable')
            ->update(['estado' => 'indisponivel']);

        DB::table('salas')
            ->where('estado', 'coming_soon')
            ->update(['estado' => 'brevemente']);

        DB::table('salas')
            ->where('estado_registo', 'active')
            ->update(['estado_registo' => 'disponivel']);

        DB::table('salas')
            ->where('estado_registo', 'blocked')
            ->update(['estado_registo' => 'bloqueado']);

        DB::table('salas')
            ->where('estado_registo', 'deleted')
            ->update(['estado_registo' => 'eliminado']);
    }
};
