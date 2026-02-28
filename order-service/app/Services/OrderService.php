<?php

namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use App\Models\OutboxEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CircuitBreaker $circuitBreaker
    ) {}

    public function getOrdersByUser(string $userId): Collection
    {
        return $this->orderRepository->getByUserId($userId);
    }

    public function createOrder(array $data, ?string $bearerToken = null, ?string $traceId = null): Order
    {
        $productResponse = $this->circuitBreaker->call(function () use ($bearerToken, $data, $traceId) {
            return Http::productService()
                ->withToken($bearerToken ?? '')
                ->withHeaders([
                    'X-Trace-Id' => $traceId ?? '',
                ])
                ->get("/products/{$data['product_id']}");
        });

        if ($productResponse instanceof \Illuminate\Http\JsonResponse) {
            throw new \RuntimeException('Service temporarily unavailable', 503);
        }
        if ($productResponse->failed()) {
            throw new \RuntimeException('Product not found or unavailable.', 404);
        }

        $productData = $productResponse->json('data') ?? $productResponse->json();
        $totalAmount = $productData['price'] * $data['quantity'];

        $reserveResponse = $this->circuitBreaker->call(function () use ($bearerToken, $data, $traceId) {
            return Http::productService()
                ->withToken($bearerToken ?? '')
                ->withHeaders([
                    'X-Trace-Id' => $traceId ?? '',
                ])
                ->post("/products/{$data['product_id']}/reserve", [
                    'quantity' => $data['quantity'],
                ]);
        });

        if ($reserveResponse instanceof \Illuminate\Http\JsonResponse) {
            throw new \RuntimeException('Service temporarily unavailable', 503);
        }
        if ($reserveResponse->failed()) {
            $err = $reserveResponse->json();
            throw new \RuntimeException('Failed to reserve stock: '.json_encode($err), 400);
        }

        return DB::transaction(function () use ($data, $totalAmount, $traceId) {
            $order = $this->orderRepository->create([
                'user_id' => $data['user_id'],
                'product_id' => $data['product_id'],
                'quantity' => $data['quantity'],
                'total_amount' => $totalAmount,
                'status' => 'pending',
            ]);

            OutboxEvent::create([
                'aggregate_type' => 'order',
                'aggregate_id' => (string) $order->id,
                'event_type' => 'order.created',
                'payload' => [
                    'order_id' => (string) $order->id,
                    'user_id' => (string) $order->user_id,
                    'amount' => (float) $order->total_amount,
                    'status' => 'pending',
                    'trace_id' => $traceId,
                ],
            ]);

            return $order;
        });
    }
}
