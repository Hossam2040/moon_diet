<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => App\Http\Middleware\AdminOnly::class,
            'force.json' => App\Http\Middleware\ForceJsonResponse::class,
        ]);
        $middleware->appendToGroup('api', App\Http\Middleware\ForceJsonResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        });
        $exceptions->render(function (RouteNotFoundException $e, $request) {
            // Common when framework tries to redirect to a non-existent 'login' route
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        });
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'message' => 'Not Found',
            ], 404);
        });
    })->create();
