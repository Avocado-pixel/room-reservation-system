<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        // SQLite doesn't enforce enum types the same way (usually stored as TEXT).
        if ($driver === 'sqlite') {
            return;
        }

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            // Best-effort: if the driver isn't MySQL/MariaDB, do not attempt raw ENUM alterations.
            return;
        }

        // users
        DB::statement("ALTER TABLE `users` MODIFY `perfil` ENUM('admin','user') NOT NULL DEFAULT 'user'");
        DB::statement("ALTER TABLE `users` MODIFY `estado` ENUM('pending','active','blocked','deleted') NOT NULL DEFAULT 'pending'");

        // salas
        DB::statement("ALTER TABLE `salas` MODIFY `estado` ENUM('available','unavailable','coming_soon') NOT NULL DEFAULT 'available'");
        DB::statement("ALTER TABLE `salas` MODIFY `estado_registo` ENUM('active','blocked','deleted') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            return;
        }

        // Revert back to legacy Portuguese enums.
        DB::statement("ALTER TABLE `users` MODIFY `perfil` ENUM('admin','utente') NOT NULL DEFAULT 'utente'");
        DB::statement("ALTER TABLE `users` MODIFY `estado` ENUM('pendente','disponivel','bloqueado','eliminado') NOT NULL DEFAULT 'pendente'");

        DB::statement("ALTER TABLE `salas` MODIFY `estado` ENUM('disponivel','indisponivel','brevemente') NOT NULL DEFAULT 'disponivel'");
        DB::statement("ALTER TABLE `salas` MODIFY `estado_registo` ENUM('disponivel','bloqueado','eliminado') NOT NULL DEFAULT 'disponivel'");
    }
};
