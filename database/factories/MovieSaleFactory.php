<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Theater;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovieSaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'theater_id' => Theater::factory(),
            'sale_date' => $this->faker->dateTimeBetween('-2 days', 'now')->format('Y-m-d'),
            'price' => "{$this->faker->randomFloat(2, 10, 100)}",
        ];
    }
}
