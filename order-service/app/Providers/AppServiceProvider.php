<?php

namespace App\Providers;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\OrderServiceInterface;
use App\Repositories\OrderRepository;
use App\Services\CircuitBreaker;
use App\Services\OrderService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->singleton(CircuitBreaker::class, CircuitBreaker::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Http::macro('productService', function () {
            return Http::baseUrl(env('PRODUCT_SERVICE_URL', 'http://orderhub-product-service:8000/api/v1'));
        });
    }
}
