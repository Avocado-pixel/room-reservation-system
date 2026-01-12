<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Room;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Only create test user if it does not exist
        if (!User::query()->where('email', 'test@example.com')->exists()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
        // Base admin (legacy-compatible). Only create if no admin exists.
        $adminExists = User::query()->where('role', 'admin')->exists();
        if (!$adminExists) {
            $plainPassword = (string) env('SAW_SEED_ADMIN_PASSWORD', '');
            if ($plainPassword === '') {
                $plainPassword = Str::random(24);
                if ($this->command) {
                    $this->command->warn('System admin created. Generated password: '.$plainPassword);
                    $this->command->warn('Set SAW_SEED_ADMIN_PASSWORD in .env to use a fixed password.');
                }
            }

            User::query()->create([
                'name' => 'System Admin',
                'email' => 'example1@example.com',
                'password' => Hash::make($plainPassword),
                'role' => 'admin',
                'status' => 'active',
                'tax_id' => '123456789',
                'phone' => '911111111',
                'address' => 'Admin address',
            ]);
        }

        // Seed rooms (only if table is empty)
        if (Room::query()->count() === 0) {
            $rooms = [
                ['name' => 'Room Alpha', 'capacity' => 10, 'status' => 'available'],
                ['name' => 'Room Beta', 'capacity' => 12, 'status' => 'available'],
                ['name' => 'Room Gamma', 'capacity' => 8, 'status' => 'coming_soon'],
            ];

            foreach ($rooms as $room) {
                Room::query()->create([...$room, 'record_status' => 'active']);
            }
        }
    }
}
