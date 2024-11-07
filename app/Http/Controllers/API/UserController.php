<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\UserRepository;
use App\Repositories\SessionRepository;
use App\Repositories\OutputRepository;

class UserController extends Controller
{
    protected UserRepository $userRepository;
    protected SessionRepository $sessionRepository;
    protected array $create_rules = [
        'first_name' => 'required|max:100',
        'last_name' => 'required|max:100',
        'cell_phone' => 'required|phone',
        'email' => 'required|email|max:100',
        'password' => 'required|alpha_num|min:8|same:password_confirm',
        'permissions' => 'nullable|exists:permissions,uuid',
        'roles' => 'nullable|exists:roles,uuid',
    ];
    protected array $update_rules = [
        'first_name' => 'sometimes|required|max:100',
        'last_name' => 'sometimes|required|max:100',
        'cell_phone' => 'sometimes|required|phone',
        'email' => 'sometimes|required|email|max:100',
        'password' => 'sometimes|required|alpha_num|min:8|same:password_confirm',
        'permissions' => 'required_unless:permissions,null,exists:permissions,uuid',
        'roles' => 'required_unless:roles,null,exists:roles,uuid',
    ];

    public function __construct(OutputRepository $output, UserRepository $userRepository, SessionRepository $sessionRepository)
    {
        parent::__construct();
        $this->output = $output;
        $this->request = request();
        $this->userRepository = $userRepository;
        $this->sessionRepository = $sessionRepository;
        $output->setRepository($this->userRepository);
        $output->repository->setAuthUser($this->user);
    }

    public function login()
    {
        try {
            $this->validate([
                'email' => 'required',
                'password' => 'required',
            ]);
            $results = $this->sessionRepository->createSessionUsingUsernamePassword($this->request);
            return $this->output->setMessages(100)->transform($results)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function logout()
    {
        try {
            $this->sessionRepository->logout($this->request->header('sid'));
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function profile()
    {
        try {
            $sid = request()->header('sid');
            $user = $this->sessionRepository->getUserBySession($sid);
            $results = $this->output->repository->show($user->uuid);
            return $this->output->setMessages(100)->transform($results)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function profileUpdate()
    {
        try {
            $sid = request()->header('sid');
            $user = $this->sessionRepository->getUserBySession($sid);
            return $this->update($user->uuid, $this->request);
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function register()
    {
        try {
            $this->validate($this->create_rules);
            $results = $this->userRepository->register($this->request->all());
            return $this->output->setMessages(100)->transform($results)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function forgotPassword()
    {
        try {
            $this->validate([
                'email' => 'required|email|exists:users,email'
            ]);
            $this->userRepository->forgotPassword($this->request->input('email'));
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function resetPassword()
    {
        try {
            $this->validate([
                'token' => 'required',
                'password' => 'required|case_diff|numbers|letters|symbols|min:6|same:password_confirm',
                'password_confirm' => 'required|same:password',
            ]);
            $this->userRepository->resetPassword($this->request->input('token'), $this->request->input('password'));
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function changePassword()
    {
        try {
            $this->validate([
                'current_password' => 'required',
                'password' => 'required|case_diff|numbers|letters|symbols|min:6|same:password_confirm',
                'password_confirm' => 'required|same:password',
            ]);
            $this->userRepository->changePassword($this->user->uuid, $this->request->input('current_password'), $this->request->input('password'));
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function verifyEmail($user, $token)
    {
        try {
            $this->userRepository->verifyEmail($user, $token);
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function verifySMS($user, $token)
    {
        try {
            $this->userRepository->verifySMS($user, $token);
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function requestVerifyEmailToken()
    {
        try {
            $this->validate([
                'user' => 'required|exists:users,uuid',
                'email' => 'required',
            ]);
            $this->userRepository->requestVerifyEmailToken($this->request->input('user'), $this->request->input('email'));
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function requestVerifySMSToken()
    {
        try {
            $this->validate([
                'user' => 'required|exists:users,uuid',
                'phone' => 'required',
            ]);
            $this->userRepository->requestVerifySMSToken($this->request->input('user'), $this->request->input('phone'));
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }
}
