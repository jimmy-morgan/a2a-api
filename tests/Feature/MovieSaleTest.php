<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\MovieSale;
use App\Models\Theater;
use Tests\TestCase;

class MovieSaleTest extends TestCase
{
    public function testGetMovieSaleSales(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $theater = Theater::factory()->create()->fresh();
        $movie_sale = MovieSale::factory()->state([
            'movie_id' => $movie->id,
            'theater_id' => $theater->id,
        ])->create()->fresh();

        $endpoint = $this->host . 'movie-sale?filters[movie.uuid]=' . $movie->uuid . '&filters[theater.uuid]=' . $theater->uuid;

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->get($endpoint);
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_count',
                    'per_page',
                    'current_page',
                    'last_page',
                    'results'
                ]
            ])
            ->assertJsonCount(1, 'data.results');
    }

    public function testGetMovieSale(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $theater = Theater::factory()->create()->fresh();
        $movie_sale = MovieSale::factory()->state([
            'movie_id' => $movie->id,
            'theater_id' => $theater->id,
        ])->create()->fresh();
        $endpoint = $this->host . 'movie-sale/' . $movie_sale->uuid . '?with[]=movie&with[]=theater';

        $this->refreshApplication();
        $response = $this->get($endpoint);
        $response->assertStatus(401);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->get($endpoint);
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'movie',
                    'theater',
                    'sale_date',
                    'price',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function testValidateMovieSale(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $theater = Theater::factory()->create()->fresh();
        $movie_sale = MovieSale::factory()->state([
            'movie_id' => $movie->id,
            'theater_id' => $theater->id,
        ])->make();
        $endpoint = $this->host . 'movie-sale' . '?with[]=movie&with[]=theater';

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'movie' => $movie->uuid,
        ]);
        $response->assertStatus(422);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'theater' => $theater->uuid,
        ]);
        $response->assertStatus(422);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'sale_date' => $movie_sale->date_sale,
        ]);
        $response->assertStatus(422);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'price' => $movie_sale->price,
        ]);
        $response->assertStatus(422);
    }

    public function testCreateMovieSale(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $theater = Theater::factory()->create()->fresh();
        $movie_sale = MovieSale::factory()->state([
            'movie_id' => $movie->id,
            'theater_id' => $theater->id,
        ])->make();

        $endpoint = $this->host . 'movie-sale' . '?with[]=movie&with[]=theater';
        $data = [
            'name' => $movie_sale->name,
            'movie' => $movie->uuid,
            'theater' => $theater->uuid,
            'sale_date' => $movie_sale->sale_date,
            'price' => $movie_sale->price,
        ];

        $this->refreshApplication();
        $response = $this->post($endpoint, $data);
        $response->assertStatus(401);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, $data);
        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'movie' => $movie_sale->movie->toArray(),
                    'theater' => $movie_sale->theater->toArray(),
                    'sale_date' => $movie_sale->sale_date,
                    'price' => $movie_sale->price,
                ]
            ]);
    }

    public function testUpdateMovieSale(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $theater = Theater::factory()->create()->fresh();
        $movie_sale = MovieSale::factory()->state([
            'movie_id' => $movie->id,
            'theater_id' => $theater->id,
        ])->create()->fresh();
        $endpoint = $this->host . 'movie-sale/' . $movie_sale->uuid . '?with[]=movie&with[]=theater';
        $data = [
            'price' => "10.00",
        ];

        $this->refreshApplication();
        $response = $this->put($endpoint, $data);
        $response->assertStatus(401);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->put($endpoint, $data);
        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'price' => $data['price'],
                ]
            ]);
    }

    public function testDeleteMovieSale(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $theater = Theater::factory()->create()->fresh();
        $movie_sale = MovieSale::factory()->state([
            'movie_id' => $movie->id,
            'theater_id' => $theater->id,
        ])->create()->fresh();
        $endpoint = $this->host . 'movie-sale/' . $movie_sale->uuid;

        $this->refreshApplication();
        $response = $this->delete($endpoint);
        $response->assertStatus(401);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->delete($endpoint);
        $response->assertStatus(200);
    }
}
