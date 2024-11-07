<?php

namespace App\Repositories;

use DB;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mail;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Jobs\SendForgotPasswordEmail;
use App\Jobs\SendPasswordResetEmail;
use App\Jobs\SendVerificationEmail;
use App\Jobs\SendVerificationSMS;

class UserRepository extends Repository
{
    use DispatchesJobs;

    protected $sessionRepository;

    public function __construct(User $user, SessionRepository $sessionRepository)
    {
        $this->init([
            'model' => $user,
            'order_by' => 'first_name',
            'sort' => 'asc'
        ]);
        $this->sessionRepository = $sessionRepository;
    }

    public function applyFilters(Request $request): Builder
    {
        $query = parent::applyFilters($request);
        if (!empty($request->filters['search.name'])) {
            $query->where(function ($q) use ($request) {
                $q->orWhere('first_name', 'like', "%{$request->filters['search.name']}%")
                    ->orWhere('last_name', 'like', "%{$request->filters['search.name']}%");
            });
        }
        return $query;
    }

    public function transform(Model $result): Model
    {
        $result = parent::transform($result);
        $result->app_version = [
            'ios' => env('APP_IOS_VERSION'),
            'android' => env('APP_ANDROID_VERSION')
        ];
        return $result;
    }

    public function preProcessing(string $action, array $data, ?Model $model = null): array
    {
        if ($action == 'store') {
            $user = User::where('email', $data['email'])->count();
            if ($user) {
                throw new \Exception('Email already exists.');
            }
            $user = User::where('cell_phone', $data['cell_phone'])->count();
            if ($user) {
                throw new \Exception('Cell phone already exists.');
            }
        } elseif ($action == 'update' && $model) {
            if (!empty($data['email'])) {
                $user = User::where('email', $data['email'])->where('id', '<>', $model->id)->count();
                if ($user) {
                    throw new \Exception('Email already exists.');
                }
                if ($model->email != $data['email']) {
                    $data['is_email_verified'] = false;
                }
            }
            if (!empty($data['cell_phone'])) {
                $user = User::where('cell_phone', $data['cell_phone'])->where('id', '<>', $model->id)->count();
                if ($user) {
                    throw new \Exception('Cell phone already exists.');
                }
                if ($model->cell_phone != $data['cell_phone']) {
                    $data['is_sms_verified'] = false;
                }
            }
        }
        $data = parent::preProcessing($action, $data, $model);
        return $data;
    }

    public function postProcessing(string $action, array $data, ?Model $model): Model
    {
        if (!empty($data['roles'])) {
            $userRoles = Role::whereIn('uuid', $data['roles'])->get();
            $model->roles()->sync($userRoles ?? []);
        }
        if (!empty($data['permissions'])) {
            $userPermissions = Permission::whereIn('uuid', $data['permissions'])->get();
            $model->permissions()->sync($userPermissions ?? []);
        }
        return parent::postProcessing($action, $data, $model);
    }

    public function sendMessage(array $data): bool
    {
        Mail::send(['emails.contact'], ['data' => $data], function ($message) use ($data) {
            $message->to('support@vacayrental.tech');
            $message->subject($data['subject']);
        });
        return true;
    }

    public function register(array $data, bool $create_session = true): User|Model
    {
        $check = $this->model->where(function ($q) use ($data) {
            $q->where('email', $data['email'])->orWhere('cell_phone', $data['cell_phone']);
        })->first();
        if ($check) {
            throw new \Exception('It appears that you already have an existing account.');
        }

        $data['email_verify_token'] = rand(100000, 999999);
        $data['sms_verify_token'] = rand(100000, 999999);
        $this->model->fill($data)->save();
        if ($create_session) {
            $request = new Request;
            $user = $this->sessionRepository->createSession($this->model, $request);
        } else {
            $user = $this->model;
        }
        return $user;
    }

    public function forgotPassword(string $email): User
    {
        $user = User::where('email', $email)->first();
        $token = rand(100000, 999999);
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => $token
        ]);
        $user->reset_token = $token;

        $job = (new SendForgotPasswordEmail($user, $token));
        dispatch($job);
        return $user;
    }

    public function verifyPasswordToken(string $token): User
    {
        $token = DB::table('password_resets')->where(['token' => $token])->first();
        if (empty($token)) {
            throw new \Exception("Password token not found", 208);
        }
        $user = User::where('email', $token->email)->first();
        if (!$user) {
            throw new \Exception("User not found", 208);
        }
        return $user;
    }

    public function resetPassword(string $password_token, string $password): User
    {
        $user = $this->verifyPasswordToken($password_token);
        $user->fill(['password' => $password, 'login_failed_attempts' => 0, 'is_pw_reset_required' => 0])->save();
        DB::table('password_resets')->where(['token' => $password_token])->delete();
        $job = (new SendPasswordResetEmail($user));
        dispatch($job);
        return $user;
    }

    public function changePassword(string $uuid, string $current_password, string $new_password): bool
    {
        $user = User::findByUuid($uuid);
        if (!empty($user) && Auth::attempt(['email' => $user->email, 'password' => $current_password])) {
            $user->fill([
                'password' => $new_password,
                'login_failed_attempts' => 0,
                'is_pw_reset_required' => 0,
            ])->save();
            $job = (new SendPasswordResetEmail($user));
            dispatch($job);
            return true;
        } else {
            throw new \Exception("Current password not valid");
        }
    }

    public function verifySMS(string $uuid, string $token): User
    {
        if (config("debug.accept_any_sms_code") == "true") {
            $user = User::where('uuid', $uuid)->first();
        } else {
            $user = User::where('sms_verify_token', $token)->where('uuid', $uuid)->first();
        }

        if ($user) {
            $user->fill(['is_sms_verified' => 1, 'sms_verify_token' => null]);
            if ($user->save()) {
                return $user;
            }
        }
        throw new \Exception("Sorry! Your SMS code isn't correct. Try again.");
    }

    public function verifyEmail(string $uuid, string $token): User
    {
        if ($user = User::where('email_verify_token', $token)->where('uuid', $uuid)->first()) {
            $user->fill(['is_email_verified' => 1, 'email_verify_token' => null])->save();
            return $user;
        }
        throw new \Exception("Sorry! Your email code isn't correct. Try again.");
    }

    public function requestVerifyEmailToken(string $uuid, string $email): bool
    {
        if ($user = User::where('uuid', $uuid)->where('email', $email)->first()) {
            $user->fill(['email_verify_token' => rand(100000, 999999)])->save();
            $job = (new SendVerificationEmail($user));
            dispatch($job);
            return true;
        }
        throw new \Exception("Email not found", 400);
    }

    public function requestVerifySMSToken(string $uuid, string $phone): bool
    {
        if ($user = User::where('uuid', $uuid)->where('cell_phone', $phone)->first()) {
            $user->fill(['sms_verify_token' => rand(100000, 999999)])->save();
            $job = (new SendVerificationSMS($user));
            dispatch($job);
            return true;
        }
        throw new \Exception("Phone not found", 400);
    }

    public function hasPermission(array $acl_permissions): bool
    {
        $roles = $this->model->roles()->whereHas('permissions', function ($q) use ($acl_permissions) {
            $q->whereIn('permissions.name', $acl_permissions);
        })->get();
        $roles_ids = $roles->pluck('id') ?? [];
        $has_permission = $this->model->permissions()->whereIn('name', $acl_permissions)->count();
        if (!empty($roles_ids)) {
            $has_permission = $this->model->roles()->whereIn('id', $roles_ids)->count();
        }
        return (bool)$has_permission;
    }

    public function hasRole(array $acl_roles): bool
    {
        $has_role = 0;
        $roles = $this->model->roles()->whereIn('name', $acl_roles)->get();
        $roles_ids = $roles->pluck('id') ?? [];
        if (!empty($roles_ids)) {
            $has_role = $this->model->roles()->whereIn('id', $roles_ids)->count();
        }
        return (bool)$has_role;
    }
}
