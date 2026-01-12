<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'capacity' => $this->faker->numberBetween(4, 20),
            'status' => 'available',
            'record_status' => 'active',
        ];
    }
}
