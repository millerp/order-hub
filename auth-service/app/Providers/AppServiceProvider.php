<?php

namespace App\Providers;

use App\Contracts\AuthServiceInterface;
use App\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            return Limit::perMinute((int) env('AUTH_LOGIN_RATE_LIMIT', 10))
                ->by($request->ip().'|'.$request->input('email', ''));
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute((int) env('AUTH_REGISTER_RATE_LIMIT', 5))
                ->by($request->ip());
        });

        RateLimiter::for('auth-refresh', function (Request $request) {
            return Limit::perMinute((int) env('AUTH_REFRESH_RATE_LIMIT', 20))
                ->by($request->ip());
        });
    }
}
