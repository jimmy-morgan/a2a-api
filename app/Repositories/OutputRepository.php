<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Log;

class OutputRepository
{
    public bool $debug = false;
    public Repository $repository;
    protected array $message = [];
    protected string $status;
    protected array|\StdClass|Model $output = [];
    protected string $format = 'json';
    protected Request $request;
    protected const MESSAGES = [
        100 => ['httpCode' => 200, 'error' => 'Request successful'],
        201 => ['httpCode' => 422, 'error' => 'Missing or invalid field'],
        204 => ['httpCode' => 401, 'error' => 'Account is locked/expired'],
        205 => ['httpCode' => 200, 'error' => 'We need to verify your email, please check your email'],
        207 => ['httpCode' => 200, 'error' => 'Password reset required'],
        208 => ['httpCode' => 401, 'error' => 'Access Code expired'],
        209 => ['httpCode' => 200, 'error' => 'We need to verify your cell phone, please check your text messages'],
        211 => ['httpCode' => 422, 'error' => 'Image upload failed'],
        212 => ['httpCode' => 401, 'error' => 'Account is disabled'],
        400 => ['httpCode' => 200, 'error' => 'No results found'],
        401 => ['httpCode' => 401, 'error' => 'Session expired'],
        402 => ['httpCode' => 401, 'error' => 'Invalid credentials provided'],
        403 => ['httpCode' => 422, 'error' => 'Record already exists'],
        405 => ['httpCode' => 415, 'error' => 'Unsupported content type'],
        406 => ['httpCode' => 401, 'error' => 'Permission denied'],
        407 => ['httpCode' => 404, 'error' => 'The account has already been registered'],
        408 => ['httpCode' => 404, 'error' => 'The username has already been taken'],
        409 => ['httpCode' => 404, 'error' => 'Item not found'],
        501 => ['httpCode' => 401, 'error' => 'Maximum login attempts, please reset your password'],
        999 => ['httpCode' => 500, 'error' => 'Unexpected error occurred']
    ];

    public function __construct()
    {
        $this->request = request();
    }

    public function setRepository(Repository $repository): void
    {
        $this->repository = $repository;
    }

    public function setMessages(\Exception|string $e): self
    {
        $this->message = [];
        $exception = '';
        $message = is_object($e) ? json_decode($e->getMessage()) : null;
        if ($message) {
            if (is_numeric($message) && isset(self::MESSAGES[$message])) {
                $this->message = ['code' => (int)$message, 'messages' => self::MESSAGES[$message]['error']];
            } else {
                $this->message = ['code' => 201, 'messages' => $message];
            }
        } else {
            if (is_object($e)) {
                if (isset(self::MESSAGES[$e->getCode()]['error'])) {
                    $code = $e->getCode();
                    $message = isset(self::MESSAGES[$e->getCode()]['error']) ? self::MESSAGES[$e->getCode()]['error'] : $e->getMessage();
                } else {
                    $code = !empty($e->getCode()) ? $e->getCode() : 201;
                    $message = $e->getMessage();
                }
                if (!env('APP_DEBUG') && strstr($message, 'SQLSTATE')) {
                    $code = 999;
                    $message = 'Unexpected error occurred';
                }
                $exception = $e->getMessage() . ' in ' . $e->getFile() . ', line ' . $e->getLine();
            } elseif (isset(self::MESSAGES[$e]['error'])) {
                $code = $e;
                $message = self::MESSAGES[$e]['error'];
            } else {
                $code = 999;
                $message = 'Unexpected error occurred';
            }
            $this->message = ['code' => (int)$code, 'messages' => $message];
            $this->message['messages'] = is_array($this->message['messages']) ? $this->message['messages'] : [$this->message['messages']];
        }
        if (!empty($exception)) {
            if ($this->debug) {
                dd($e);
            }
            if (isset($this->request)) {
                $method = $this->request->method();
                $params = $this->request->all();
                $url = $this->request->url();
            } else {
                $method = $params = $url = '';
            }
            if ($code == 400) {
                if (env('APP_DEBUG')) {
                    Log::info($exception . "; Request: " . $method . " " . $url . ", params: " . json_encode($params) . ", sid:" . $this->request->header('sid'));
                }
            } else {
                Log::error($exception . "; Request: " . $method . " " . $url . ", params: " . json_encode($params) . ", sid:" . $this->request->header('sid'));
            }
        }
        return $this;
    }

    public function setStatus(?string $status): self
    {
        if (!empty($status)) {
            $this->status = $status;
            return $this;
        }
        if (empty($this->messages_output)) {
            if (!empty($status) && config("errors.$status")) {
                $this->message = ['code' => 999, 'message' => config("errors.$status")];
            }
        }
        $this->status = empty($this->status) ? $this->getStatus() : $this->status;
        return $this;
    }

    public function setOutput(array|\StdClass $output): self
    {
        $this->output = is_array($output) ? (object)$output : $output;
        return $this;
    }

    public function transform(Collection|Model|array $collection, string $type = ''): self
    {
        $this->output = new \stdClass();

        if ($type == 'count') {
            $this->output->total_count = $collection;
            return $this;
        } elseif ($type == 'index') {
            $this->output->total_count = isset($collection[$this->getModelNamegetStatus() . '_count']) ? $collection[$this->getModelNamegetStatus() . '_count'] : 1;
            $this->output->per_page = !empty(request()->input('per_page')) ? (int)request()->input('per_page') : 10;
            $this->output->current_page = !empty(request()->input('page')) ? (int)request()->input('page') : 1;
            $this->output->last_page = ceil($this->output->total_count / $this->output->per_page);
        }

        $process_row = function ($row) use ($type) {
            return $this->repository->transform($row);
        };

        $objectname = strtolower(str_ireplace(['app\\', 'repositories', 'repository', '\\'], ['', '', '', ''], get_class($this->repository)));
        if (!empty($collection[$objectname]) && is_object($collection[$objectname]) && get_class($collection[$objectname]) == 'Illuminate\Pagination\LengthAwarePaginator') {
            foreach ($collection[$objectname] as $row) {
                if ($type == 'index') {
                    $this->output->results[] = $process_row($row);
                } else {
                    $this->output = $process_row($row);
                }
            }
        } else {
            $this->output = !empty($collection[$objectname]) ? $collection[$objectname] : $collection;
        }
        return $this;
    }

    public function render(): JsonResponse
    {
        $this->setStatus(null);
        if (!empty($this->message) && $this->status != 200) {
            if (isset($this->request)) {
                $method = $this->request->method();
                $params = $this->request->all();
                $url = $this->request->url();
            } else {
                $method = $params = $url = '';
            }
            Log::error(json_encode($this->message) . "; Request: " . $method . " " . $url . ", params: " . json_encode($params));
        }
        $format = $this->format;
        if (env('APP_DEBUG') && isset($this->request)) {
            if (env('APP_DEBUG_REQUEST_ONLY') == true) {
                Log::info($this->request->method() . ' ' . $this->request->url() . ': params: ' . json_encode($this->request->all()) . ', sid:' . $this->request->header('sid'));
            } else {
                Log::info($this->request->method() . ' ' . $this->request->url() . ': params: ' . json_encode($this->request->all()) . ', sid:' . $this->request->header('sid') . ', output: ' . json_encode(['message' => $this->message, 'data' => $this->output]));
            }
        }
        $this->output = is_array($this->output) ? (object)$this->output : $this->output;
        return response()->$format([
            'message' => $this->message,
            'data' => $this->output
        ], $this->status);
    }

    private function getStatus(): int|string
    {
        return isset($this->message['code']) && array_key_exists($this->message['code'], self::MESSAGES) ? self::MESSAGES[$this->message['code']]['httpCode'] : 500;
    }

    private function getModelNamegetStatus(): string
    {
        return strtolower(str_ireplace(['app\\', 'repositories', 'repository', '\\'], ['', '', '', ''], get_class($this->repository)));
    }
}
