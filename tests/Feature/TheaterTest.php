<?php

namespace Tests\Feature;

use App\Models\Theater;
use Tests\TestCase;

class TheaterTest extends TestCase
{
    public function testGetTheaters(): void
    {
        $theater = Theater::factory()->create()->fresh();
        $endpoint = $this->host . 'theater?filters[search]=' . $theater->name;

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

    public function testGetTheater(): void
    {
        $theater = Theater::factory()->create()->fresh();
        $endpoint = $this->host . 'theater/' . $theater->uuid;

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
                    'address',
                    'created_at',
                    'updated_at',
                ]
            ]);
    }

    public function testValidateTheater(): void
    {
        $theater = Theater::factory()->make();
        $endpoint = $this->host . 'theater';

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'name' => $theater->name,
        ]);
        $response->assertStatus(422);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'address' => $theater->address,
        ]);
        $response->assertStatus(422);
    }

    public function testCreateTheater(): void
    {
        $theater = Theater::factory()->make();
        $endpoint = $this->host . 'theater';
        $data = [
            'name' => $theater->name,
            'address' => $theater->address,
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
                    'name' => $theater->name,
                    'address' => $theater->address
                ]
            ]);
    }

    public function testUpdateTheater(): void
    {
        $theater = Theater::factory()->create()->fresh();
        $endpoint = $this->host . 'theater/' . $theater->uuid;
        $data = [
            'name' => $theater->name,
            'address' => $theater->address,
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
                    'address' => $data['address'],
                ]
            ]);
    }

    public function testDeleteTheater(): void
    {
        $theater = Theater::factory()->create()->fresh();
        $endpoint = $this->host . 'theater/' . $theater->uuid;

        $this->refreshApplication();
        $response = $this->delete($endpoint);
        $response->assertStatus(401);

        $this->refreshApplication();
        $response = $this->withHeaders($this->headers)->delete($endpoint);
        $response->assertStatus(200);
    }
}
