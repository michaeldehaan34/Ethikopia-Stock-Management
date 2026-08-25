<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\SessionAuth;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load the role-based auth routes (login / logout for the migrated
            // RoleLoginController). Without this the `login` named route used by
            // the SessionAuth middleware is never registered.
            require base_path('routes/auth.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Nonaktifkan verifikasi CSRF saat pengujian (testing) agar fitur
        // test dapat mem-POST tanpa token. Tidak memengaruhi produksi
        // (APP_ENV selain "testing") maupun UI.
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['*']);
        }

        // Mengizinkan Reverse Proxy (seperti Cloudflare) agar protokol HTTPS 
        // terdeteksi dengan benar dan URL aset tidak berubah menjadi HTTP.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.session' => AuthenticateSession::class,
            'session.auth' => SessionAuth::class,
            'role' => RoleMiddleware::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'password.confirm' => RequirePassword::class,
            'precognitive' => HandlePrecognitiveRequests::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();