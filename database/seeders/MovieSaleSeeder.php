<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MovieSale;
use App\Models\Movie;
use App\Models\Theater;

class MovieSaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $movies = Movie::factory()->count(2)->create();
        $theaters = Theater::factory()->count(2)->create();

        foreach ($movies as $movie) {
            foreach ($theaters as $theater) {
                MovieSale::factory()
                    ->count(2)
                    ->state([
                        'movie_id' => $movie->id,
                        'theater_id' => $theater->id,
                    ])
                    ->create();
            }
        }
    }
}
