<?php

namespace Database\Factories;

use App\Models\ClinicLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicLocationFactory extends Factory
{
    protected $model = ClinicLocation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(29, 31),
            'longitude' => $this->faker->longitude(30, 32),
        ];
    }
}
