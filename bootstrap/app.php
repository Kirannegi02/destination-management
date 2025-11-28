<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Load JWT exception stubs if the package is not installed
if (!class_exists('Tymon\JWTAuth\Exceptions\JWTException')) {
    require_once __DIR__ . '/../app/Support/JWTExceptionStub.php';
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle JWT authentication exceptions for API routes
        $exceptions->render(function (Tymon\JWTAuth\Exceptions\TokenExpiredException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token has expired',
                    'error' => 'Token expired'
                ], 401);
            }
        });

        $exceptions->render(function (Tymon\JWTAuth\Exceptions\TokenInvalidException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token is invalid',
                    'error' => 'Token invalid'
                ], 401);
            }
        });

        $exceptions->render(function (Tymon\JWTAuth\Exceptions\JWTException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token error',
                    'error' => 'Token error'
                ], 401);
            }
        });

        // Handle authentication exceptions for API routes
        $exceptions->render(function (Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'error' => 'Authentication required'
                ], 401);
            }
        });
    })->create();
