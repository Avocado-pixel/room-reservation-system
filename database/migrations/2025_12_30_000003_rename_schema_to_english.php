<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        $driver = DB::getDriverName();

        // This migration is written for MySQL/MariaDB (the typical production target).
        // For SQLite or other drivers, we create the English-named tables instead of renaming
        // so the rest of the migrations (and tests) can run against the expected schema.
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            $this->ensureEnglishTables();
            return;
        }

        // 1) Rename tables
        if (Schema::hasTable('salas') && !Schema::hasTable('rooms')) {
            DB::statement('RENAME TABLE `salas` TO `rooms`');
        }

        // Drop FK that points to salas before renaming reservas -> bookings / sala_id -> room_id
        if (Schema::hasTable('reservas')) {
            try {
                DB::statement('ALTER TABLE `reservas` DROP FOREIGN KEY `reservas_sala_id_foreign`');
            } catch (Throwable $e) {
                // Ignore if FK name differs or already dropped.
            }
        }

        if (Schema::hasTable('reservas') && !Schema::hasTable('bookings')) {
            DB::statement('RENAME TABLE `reservas` TO `bookings`');
        }

        // 2) Rename columns (rooms)
        if (Schema::hasTable('rooms')) {
            $this->renameColumnIfExists('rooms', 'nome', 'name', "ALTER TABLE `rooms` CHANGE `nome` `name` VARCHAR(255) NOT NULL");
            $this->renameColumnIfExists('rooms', 'capacidade', 'capacity', "ALTER TABLE `rooms` CHANGE `capacidade` `capacity` INT UNSIGNED NOT NULL DEFAULT 1");
            $this->renameColumnIfExists('rooms', 'estado', 'status', "ALTER TABLE `rooms` CHANGE `estado` `status` ENUM('available','unavailable','coming_soon') NOT NULL DEFAULT 'available'");
            $this->renameColumnIfExists('rooms', 'foto', 'photo', "ALTER TABLE `rooms` CHANGE `foto` `photo` VARCHAR(255) NULL");
            $this->renameColumnIfExists('rooms', 'estado_registo', 'record_status', "ALTER TABLE `rooms` CHANGE `estado_registo` `record_status` ENUM('active','blocked','deleted') NOT NULL DEFAULT 'active'");

            // Align composite index name/columns (best-effort)
            try {
                DB::statement('ALTER TABLE `rooms` DROP INDEX `salas_estado_registo_estado_index`');
            } catch (Throwable $e) {
                // ignore
            }
            try {
                DB::statement('ALTER TABLE `rooms` ADD INDEX `rooms_record_status_status_index` (`record_status`, `status`)');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // 3) Rename columns (bookings)
        if (Schema::hasTable('bookings')) {
            $this->renameColumnIfExists('bookings', 'sala_id', 'room_id', "ALTER TABLE `bookings` CHANGE `sala_id` `room_id` BIGINT UNSIGNED NOT NULL");
            $this->renameColumnIfExists('bookings', 'data_inicio', 'start_at', "ALTER TABLE `bookings` CHANGE `data_inicio` `start_at` DATETIME NOT NULL");
            $this->renameColumnIfExists('bookings', 'data_fim', 'end_at', "ALTER TABLE `bookings` CHANGE `data_fim` `end_at` DATETIME NOT NULL");
            $this->renameColumnIfExists('bookings', 'token_partilha', 'share_token', "ALTER TABLE `bookings` CHANGE `token_partilha` `share_token` VARCHAR(255) NULL");

            // Recreate FK to rooms
            try {
                DB::statement('ALTER TABLE `bookings` ADD CONSTRAINT `bookings_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE');
            } catch (Throwable $e) {
                // ignore if already exists
            }

            // Best-effort index alignment
            try {
                DB::statement('ALTER TABLE `bookings` DROP INDEX `reservas_sala_id_data_inicio_index`');
            } catch (Throwable $e) {
                // ignore
            }
            try {
                DB::statement('ALTER TABLE `bookings` DROP INDEX `reservas_user_id_data_inicio_index`');
            } catch (Throwable $e) {
                // ignore
            }
            try {
                DB::statement('ALTER TABLE `bookings` ADD INDEX `bookings_room_id_start_at_index` (`room_id`, `start_at`)');
                DB::statement('ALTER TABLE `bookings` ADD INDEX `bookings_user_id_start_at_index` (`user_id`, `start_at`)');
            } catch (Throwable $e) {
                // ignore
            }
        }

        // 4) Rename columns (users)
        if (Schema::hasTable('users')) {
            $this->renameColumnIfExists('users', 'nif', 'tax_id', "ALTER TABLE `users` CHANGE `nif` `tax_id` VARCHAR(20) NULL");
            $this->renameColumnIfExists('users', 'telemovel', 'phone', "ALTER TABLE `users` CHANGE `telemovel` `phone` VARCHAR(20) NULL");
            $this->renameColumnIfExists('users', 'morada', 'address', "ALTER TABLE `users` CHANGE `morada` `address` VARCHAR(255) NULL");
            $this->renameColumnIfExists('users', 'perfil', 'role', "ALTER TABLE `users` CHANGE `perfil` `role` ENUM('admin','user') NOT NULL DEFAULT 'user'");
            $this->renameColumnIfExists('users', 'estado', 'status', "ALTER TABLE `users` CHANGE `estado` `status` ENUM('pending','active','blocked','deleted') NOT NULL DEFAULT 'pending'");
            $this->renameColumnIfExists('users', 'token_validacao_email', 'email_validation_token', "ALTER TABLE `users` CHANGE `token_validacao_email` `email_validation_token` VARCHAR(64) NULL");
            $this->renameColumnIfExists('users', 'token_validacao_email_expira', 'email_validation_expires_at', "ALTER TABLE `users` CHANGE `token_validacao_email_expira` `email_validation_expires_at` DATETIME NULL");

            // Unique index on tax_id (best-effort)
            try {
                DB::statement('ALTER TABLE `users` DROP INDEX `users_nif_unique`');
            } catch (Throwable $e) {
                // ignore
            }
            try {
                DB::statement('ALTER TABLE `users` ADD UNIQUE `users_tax_id_unique` (`tax_id`)');
            } catch (Throwable $e) {
                // ignore
            }
        }
    }

    private function renameColumnIfExists(string $table, string $from, string $to, string $statement): void
    {
        if (!Schema::hasColumn($table, $from) || Schema::hasColumn($table, $to)) {
            return;
        }

        try {
            DB::statement($statement);
        } catch (Throwable $e) {
            // Ignore if the database differs slightly (best-effort migration).
        }
    }

    public function down(): void
    {
        // Intentionally not implemented: full rollback of table/column renames is destructive and
        // rarely needed. If you need rollback support, we can add it with explicit reverse steps.
    }

    private function ensureEnglishTables(): void
    {
        // Ensure the users table exposes the English columns expected by the application/tests.
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tax_id')) {
                $table->string('tax_id', 20)->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->string('address', 255)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'user'])->default('user')->after('address');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['pending', 'active', 'blocked', 'deleted'])->default('pending')->after('role');
            }
            if (!Schema::hasColumn('users', 'email_validation_token')) {
                $table->string('email_validation_token', 64)->nullable()->after('status');
            }
            if (!Schema::hasColumn('users', 'email_validation_expires_at')) {
                $table->dateTime('email_validation_expires_at')->nullable()->after('email_validation_token');
            }
        });

        if (!Schema::hasTable('rooms')) {
            Schema::create('rooms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedInteger('capacity')->default(1);
                $table->enum('status', ['available', 'unavailable', 'coming_soon'])->default('available');
                $table->string('photo')->nullable();
                $table->enum('record_status', ['active', 'blocked', 'deleted'])->default('active');
                $table->timestamps();
                $table->index(['record_status', 'status']);
            });
        }

        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->string('share_token', 255)->nullable();
                $table->timestamps();
                $table->index(['room_id', 'start_at']);
                $table->index(['user_id', 'start_at']);
            });
        }
    }
};
