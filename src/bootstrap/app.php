<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \Laravel\Passport\Http\Middleware\CreateFreshApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AccessDeniedHttpException $exception, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }

            return redirect()->to('/')->with('error_alert', 'You have no access for this page.');
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Not Found'], Response::HTTP_NOT_FOUND);
            }

            return redirect()->back()->with('error_alert', 'The resource you are looking for does not exist.');
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($exception instanceof \Illuminate\Validation\ValidationException
                || $exception instanceof \Illuminate\Auth\AuthenticationException) {
                return null;
            }

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return redirect()->back()->with('error_alert', 'Something went wrong.');
        });
    })->create();
