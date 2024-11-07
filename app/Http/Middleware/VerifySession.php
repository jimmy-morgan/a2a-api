<?php

namespace App\Http\Middleware;

use Closure;
use Auth;
use App\Repositories\OutputRepository;
use App\Models\Session;

class VerifySession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $output = new OutputRepository();
        if ($request->header('sid')) {
            if ($session = Session::find($request->header('sid'))) {
                Auth::loginUsingId($session->user_id);
                if ($request->header('No-Session-Refresh') !== "true") {
                    $session->last_activity = time();
                    $session->save();
                }
                if (!$session->user->is_active) {
                    return $output->setMessages(204)->render();
                }
                return $next($request);
            }
        }
        return $output->setMessages(401)->render();
    }
}
