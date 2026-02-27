<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
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
                if ($e instanceof \Illuminate\Auth\AuthenticationException || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 403) {
                    $status = $e->getStatusCode() ?? 401;
                }
                if ($status >= 500) {
                    return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
                }
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'type' => class_basename($e),
                ], $status);
            }
        });
    })->create();
