<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if (($request->ajax() || env('APP_TYPE') == 'api') && !$request->header('debug')) {
            $status = $exception->getCode();
            $message = config("errors.$status");
            $message = !empty($message) ? $message : $exception->getMessage();
            $status = !empty($status) ? $status : 500;
            return response(json_encode([
                'messages' => [
                    'code' => 999,
                    'message' => $message,
                    'details' => env('APP_DEBUG') ? $exception->getTrace() : null,
                ]
            ]), $status)
                ->header('Content-Type', 'text/json');
        }
        return parent::render($request, $exception);
    }
}
