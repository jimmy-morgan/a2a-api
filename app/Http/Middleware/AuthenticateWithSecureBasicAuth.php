<?php

namespace App\Http\Middleware;

use Closure;
use App\Repositories\OutputRepository;
use Log;

class AuthenticateWithSecureBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $output = new OutputRepository();
        $errors = [];

        if ($request->method() == 'OPTIONS' || $request->path() == 'api') return $next($request);
        $api_key = env('API_KEY') ?? null;

        if (!empty($api_key) && $request->header("api-key") == $api_key) {
            return $next($request);
        } else {
            $errors[] = "key: " . $request->header("api-key");
            Log::error(__METHOD__ . '() - ' . implode(', ', $errors));
            return $output->setMessages(402)->render();
        }
    }
}
