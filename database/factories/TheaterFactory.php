<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TheaterFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'address' => $this->faker->address(),
        ];
    }
}
