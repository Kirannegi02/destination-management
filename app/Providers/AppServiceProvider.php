<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Support\JWTGuard;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom JWT guard if the package is not installed
        if (!class_exists('Tymon\JWTAuth\JWTGuard')) {
            Auth::extend('jwt', function ($app, $name, array $config) {
                return new JWTGuard(
                    Auth::createUserProvider($config['provider'] ?? null),
                    $app['request']
                );
            });
        }
    }
}
