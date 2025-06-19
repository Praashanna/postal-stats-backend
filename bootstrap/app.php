<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return match (true) {
                    $e instanceof \Illuminate\Auth\AuthenticationException => response()->json([
                        'status' => 'error',
                        'message' => 'Unauthenticated',
                        'error' => 'Please provide a valid authentication token'
                    ], 401),
                    
                    $e instanceof \Illuminate\Auth\Access\AuthorizationException => response()->json([
                        'status' => 'error',
                        'message' => 'Forbidden',
                        'error' => $e->getMessage()
                    ], 403),
                    
                    $e instanceof \Illuminate\Validation\ValidationException => response()->json([
                        'status' => 'error',
                        'message' => 'Validation failed',
                        'errors' => $e->errors()
                    ], 422),
                    
                    $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException => response()->json([
                        'status' => 'error',
                        'message' => 'Resource not found',
                        'error' => config('app.env') === 'production' 
                            ? 'The requested resource was not found' 
                            : $e->getMessage()
                    ], 404),
                    
                    $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException => response()->json([
                        'status' => 'error',
                        'message' => 'Route not found',
                        'error' => 'The requested route does not exist'
                    ], 404),
                    
                    $e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException => response()->json([
                        'status' => 'error',
                        'message' => 'Method not allowed',
                        'error' => $e->getMessage()
                    ], 405),
                    
                    $e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException => response()->json([
                        'status' => 'error',
                        'message' => 'Token expired',
                        'error' => 'The provided token has expired'
                    ], 401),
                    
                    $e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException => response()->json([
                        'status' => 'error',
                        'message' => 'Token invalid',
                        'error' => 'The provided token is invalid'
                    ], 401),
                    
                    $e instanceof \Tymon\JWTAuth\Exceptions\JWTException => response()->json([
                        'status' => 'error',
                        'message' => 'Token error',
                        'error' => 'Authentication token is required'
                    ], 401),
                    
                    str_contains($e->getMessage(), 'Route [login] not defined') => response()->json([
                        'status' => 'error',
                        'message' => 'Unauthenticated',
                        'error' => 'Please provide a valid authentication token'
                    ], 401),
                    
                    default => (function() use ($e) {
                        Log::error('Unhandled exception: ' . $e->getMessage(), [
                            'exception' => $e,
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                        ]);
                        
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Internal server error',
                            'error' => config('app.env') === 'production' 
                                ? 'Something went wrong' 
                                : $e->getMessage()
                        ], 500);
                    })()
                };
            }
        });
    })->create();
