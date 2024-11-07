<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Auth;
use Hash;
use Carbon\Carbon;
use App\Models\Session;
use App\Models\User;

class SessionRepository extends Repository
{
    public function __construct()
    {
        $this->init([
            'model' => new Session,
            'order_by' => 'last_activity',
            'sort' => 'desc'
        ]);
    }

    public function createSessionUsingUsernamePassword(Request $request): Model
    {
        $user = Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password')]);
        $now = time();

        if ($user) {
            $user = User::where('email', $request->input('email'))->first();

            if ($user->login_failed_attempts >= 3) {
                $user->is_pw_reset_required = 1;
                $user->save();
                throw new \Exception("Too many login attempts", 501);
            }
            if (!$user->is_active) {
                throw new \Exception("User is not active", 204);
            }
            if ($user->is_locked) {
                throw new \Exception("Account is locked", 212);
            }
            if (!empty($user->expires_at) && strtotime($user->expires_at) < $now) {
                throw new \Exception("User is expired", 204);
            }

            $user->login_count++;
            $user->login_failed_attempts = 0;
            if (!empty($request->remember_token)) {
                $user->remember_token = Hash::make($request->remember_token);
            }
            $user->save();
            return $this->createSession($user, $request);
        } else {
            $user = User::where('email', $request->input('email'))->first();
            if ($user) {
                $user->login_failed_attempts++;
                if ($user->login_failed_attempts >= 5) {
                    $user->is_pw_reset_required = 1;
                    $user->save();
                    throw new \Exception("Too many login attempts", 501);
                }
                $user->save();
            }
        }
        throw new \Exception("Invalid credentials", 402);
    }

    public function createSession(User|Model $user, Request $request): Model
    {
        $session_id = Str::random(64);

        $session = Session::create([
            'id' => $session_id,
            'user_id' => $user->id,
            'ip_address' => $request->input('ip'),
            'last_activity' => time(),
            'payload' => $request->input('payload')
        ]);
        $session->id = $session_id;
        $user->lastlogin_at = new Carbon();
        $user->save();
        $result = $this->getUserBySession($session_id);
        return $result;
    }

    public function getUserBySession(string $sid): ?Model
    {
        if ($session = Session::where('id', $sid)->first()) {
            $user = User::where('id', $session->user_id)->first();
            $result = User::with(['sessions' => function ($q) use ($sid) {
                $q->where('id', $sid);
            }])->where('id', $user->id)->first();
            return $result;
        }
        return null;
    }

    public function logout(string $sid): bool
    {
        $session = Session::where('id', $sid)->first();

        if (!$session) {
            throw new \Exception("Invalid sid", 401);
        }
        $session->delete();
        return true;
    }
}
