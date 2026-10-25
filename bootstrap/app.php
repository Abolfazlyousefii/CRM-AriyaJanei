<?php

use App\Http\Middleware\CheckIfBlocked;
use App\Http\Middleware\CheckVisitorAccess;
use App\Http\Middleware\EnsureExternalSyncToken;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureUserIsActive::class,
            CheckIfBlocked::class,
        ]);

        $middleware->alias([
            // Spatie
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,

            // ✅ Middleware خودت
            'visitor.access' => CheckVisitorAccess::class,
            'external.sync' => EnsureExternalSyncToken::class,
            'active' => EnsureUserIsActive::class,
            'blocked' => CheckIfBlocked::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (HttpException $exception, Request $request) {
            if ($exception->getStatusCode() === 419 && $request->isMethod('POST') && $request->is('login') && ! $request->expectsJson()) {
                return redirect()->route('login')
                    ->withInput($request->only('phone'))
                    ->with('status', 'اعتبار صفحه ورود به‌روز شد. لطفاً دوباره وارد شوید.')
                    ->header('Cache-Control', 'no-store, private');
            }
        });
    })
    ->create();
