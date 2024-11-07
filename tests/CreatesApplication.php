<?php

namespace Tests;

use App\Models\Session;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */

    protected $host;
    protected $headers;

    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $user = User::factory()->create()->fresh();
        $session = Session::factory()->state([
            'user_id' => $user->id,
        ])->create()->fresh();

        $this->host = env('APP_URL') . '/api/';
        $this->headers = [
            'api-key' => env('API_KEY'),
            'sid' => $session->id,
        ];

        return $app;
    }
}
