<?php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SecurityHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(SecurityHeaders::class);
        $middleware->web(append: [App\Http\Middleware\HandleInertiaRequests::class], replace: [
            Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => App\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $middleware->alias(['active' => App\Http\Middleware\EnsureActiveUser::class, 'admin' => App\Http\Middleware\EnsureAdmin::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/inicio');
        $middleware->trimStrings(except: ['contrasena', 'password']);
        $proxies = array_filter(array_map('trim', explode(',', (string)env('TRUSTED_PROXIES', ''))));
        if ($proxies) $middleware->trustProxies(at: $proxies);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['contrasena', 'password', 'password_confirmation']);
        $exceptions->respond(fn (Symfony\Component\HttpFoundation\Response $response) => app(SecurityHeaders::class)->apply(request(), $response));
    })->create();