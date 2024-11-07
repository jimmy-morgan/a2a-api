<?php

namespace App\Http\Controllers;

use App\Repositories\OutputRepository;
use App\Repositories\SessionRepository;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Validator;
use App\Models\User;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected OutputRepository $output;
    protected ?User $user = null;
    protected bool $hasUserFilter = false;
    protected Request $request;

    public function __construct()
    {
        $this->request = request();
        $sessionRepository = new SessionRepository();
        $sid = request()->header('sid');
        if (!empty($sid)) {
            $this->hasUserFilter = true;
            $user = $sessionRepository->getUserBySession($sid);
            if ($user_uuid = Route::current()->parameter('user')) {
                $user = User::findByUuid($user_uuid);
            }
            $this->user = $user ?? null;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            } else {
                $request->merge(['user' => $this->user->uuid]);
            }
        }
        try {
            $results = $this->output->repository->get($request);
            return $this->output->setMessages(100)->transform($results, 'index')->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            } else {
                $request->merge([
                    'user' => $this->user->uuid,
                    'created_by_user_id' => $this->user->id,
                    'updated_by_user_id' => $this->user->id,
                ]);
            }
        }
        try {
            if (!empty($this->create_rules)) {
                $this->validate($this->create_rules);
            }
            $results = $this->output->repository->store($request->all());
            return $this->output->setMessages(100)->transform($results)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    /**
     * Copy the specified resource.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function copy(string $uuid, Request $request): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            } else {
                $request->merge([
                    'user' => $this->user->uuid,
                    'created_by_user_id' => $this->user->id,
                    'updated_by_user_id' => $this->user->id,
                ]);
            }
        }
        try {
            $results = $this->output->repository->copy($uuid, $request->all());
            return $this->output->setMessages(100)->transform($results)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param string $uuid
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            }
        }
        try {
            $results = $this->output->repository->show($uuid);
            return $this->output->setMessages(100)->transform($results)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param string $uuid
     * @return JsonResponse
     */
    public function update(string $uuid, Request $request): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            } else {
                $request->merge([
                    'user' => $this->user->uuid,
                    'created_by_user_id' => $this->user->id,
                    'updated_by_user_id' => $this->user->id,
                ]);
            }
        }
        try {
            if (!empty($this->update_rules)) {
                $this->validate($this->update_rules);
            }
            $results = $this->output->repository->update($uuid, $request->all());
            return $this->output->setMessages(100)->transform($results)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    /**
     * Update the specified resources in storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMultiple(Request $request): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            }
        }
        try {
            $this->output->repository->updateMultiple($request);
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param string $uuid
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(string $uuid, Request $request): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            }
        }
        try {
            $this->output->repository->destroy($uuid, $request);
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    /**
     * Remove the specified resources from storage.
     * @param Request $request
     * @return JsonResponse
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        if ($this->hasUserFilter) {
            if (!$this->user) {
                return $this->output->setMessages(400)->render();
            }
        }
        try {
            $this->output->repository->destroyMultiple($request);
            return $this->output->setMessages(100)->render();
        } catch (\Exception $e) {
            return $this->output->setMessages($e)->render();
        }
    }

    public function validate(array $rules, array $custom_messages = []): bool
    {
        $validator = Validator::make(request()->all(), $rules, $custom_messages);
        $messages = Arr::flatten($validator->messages()->toArray());
        if ($validator->fails()) {
            throw new \Exception(json_encode($messages), 201);
        }
        return true;
    }
}
