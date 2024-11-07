<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Theater;

class TheaterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Theater::factory()
            ->count(2)
            ->create();
    }
}
