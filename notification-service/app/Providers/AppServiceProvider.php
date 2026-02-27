<?php

namespace App\Providers;

use App\Contracts\NotificationRepositoryInterface;
use App\Contracts\NotificationServiceInterface;
use App\Repositories\NotificationRepository;
use App\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
