<?php

namespace Tests\Feature;

use App\Models\Movie;
use Tests\TestCase;

class MovieTest extends TestCase
{
    public function testGetMovies(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $endpoint = $this->host . 'movie?filters[search]=' . $movie->name;

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

    public function testGetMovie(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $endpoint = $this->host . 'movie/' . $movie->uuid;

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
                    'name',
                    'description',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function testValidateMovie(): void
    {
        $movie = Movie::factory()->make();
        $endpoint = $this->host . 'movie';

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'name' => $movie->name,
        ]);
        $response->assertStatus(422);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'description' => $movie->description,
        ]);
        $response->assertStatus(422);
    }

    public function testCreateMovie(): void
    {
        $movie = Movie::factory()->make();
        $endpoint = $this->host . 'movie';
        $data = [
            'name' => $movie->name,
            'description' => $movie->description,
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
                    'name' => $movie->name,
                    'description' => $movie->description
                ]
            ]);
    }

    public function testUpdateMovie(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $endpoint = $this->host . 'movie/' . $movie->uuid;
        $data = [
            'name' => $movie->name,
            'description' => $movie->description,
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
                    'name' => $data['name'],
                    'description' => $data['description'],
                ]
            ]);
    }

    public function testDeleteMovie(): void
    {
        $movie = Movie::factory()->create()->fresh();
        $endpoint = $this->host . 'movie/' . $movie->uuid;

        $this->refreshApplication();
        $response = $this->delete($endpoint);
        $response->assertStatus(401);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->delete($endpoint);
        $response->assertStatus(200);
    }
}
