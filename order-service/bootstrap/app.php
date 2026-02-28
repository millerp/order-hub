<?php

use App\Http\Middleware\AttachRequestId;
use App\Http\Middleware\AttachTraceId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AttachRequestId::class);
        $middleware->append(AttachTraceId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->wantsJson();
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->wantsJson()) {
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    $status = 422;
                }
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                }
                if ($status >= 500) {
                    return response()->json([
                        'message' => 'Internal server error',
                        'errors' => [
                            ['message' => 'Internal server error'],
                        ],
                        'meta' => [
                            'request_id' => $request->attributes->get('request_id'),
                            'trace_id' => $request->attributes->get('trace_id'),
                        ],
                        'request_id' => $request->attributes->get('request_id'),
                        'trace_id' => $request->attributes->get('trace_id'),
                    ], 500);
                }

                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => [
                        [
                            'message' => $e->getMessage(),
                            'type' => class_basename($e),
                        ],
                    ],
                    'meta' => [
                        'request_id' => $request->attributes->get('request_id'),
                        'trace_id' => $request->attributes->get('trace_id'),
                    ],
                    'type' => class_basename($e),
                    'request_id' => $request->attributes->get('request_id'),
                    'trace_id' => $request->attributes->get('trace_id'),
                ], $status);
            }
        });
    })->create();
