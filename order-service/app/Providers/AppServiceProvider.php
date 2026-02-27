<?php

namespace App\Providers;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\OrderServiceInterface;
use App\Repositories\OrderRepository;
use App\Services\CircuitBreaker;
use App\Services\OrderService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;

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
            return Http::acceptJson()
                ->baseUrl(env('PRODUCT_SERVICE_URL', 'http://orderhub-product-service:8000/api/v1'))
                ->connectTimeout((float) env('PRODUCT_SERVICE_CONNECT_TIMEOUT', 1))
                ->timeout((float) env('PRODUCT_SERVICE_TIMEOUT', 2))
                ->retry(
                    (int) env('PRODUCT_SERVICE_RETRIES', 2),
                    (int) env('PRODUCT_SERVICE_RETRY_SLEEP_MS', 200),
                    fn (\Throwable $exception) => $exception instanceof ConnectionException,
                    throw: false
                );
        });
    }
}
