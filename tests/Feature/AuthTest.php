<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function testLoginFailed(): void
    {
        unset($this->headers['sid']);
        $user = User::factory()->create()->fresh();
        $endpoint = $this->host . 'auth/login';

        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'email' => $user->email,
            'password' => 'invalid_password',
        ]);
        $response
            ->assertStatus(401)
            ->assertJson([
                'message' => [
                    'code' => 402
                ]
            ]);
    }

    public function testLoginSuccessful(): void
    {
        unset($this->headers['sid']);
        $user = User::factory()->create()->fresh();
        $endpoint = $this->host . 'auth/login';

        $response = $this->withHeaders($this->headers)->post($endpoint, [
            'email' => $user->email,
            'password' => 'Testing123#',
        ]);
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'sessions' => [
                        [
                            'id'
                        ]
                    ]
                ]
            ])
            ->assertJson([
                'data' => [
                    'uuid' => $user->uuid
                ]
            ]);
    }
}
