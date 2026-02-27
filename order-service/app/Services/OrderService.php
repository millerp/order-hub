<?php

namespace App\Services;

use App\Contracts\OrderRepositoryInterface;
use App\Contracts\OrderServiceInterface;
use App\Models\Order;
use App\Services\CircuitBreaker;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private CircuitBreaker $circuitBreaker
    ) {
    }

    public function getOrdersByUser(string $userId): Collection
    {
        return $this->orderRepository->getByUserId($userId);
    }

    public function createOrder(array $data, string $bearerToken): Order
    {
        $productResponse = $this->circuitBreaker->call(function () use ($bearerToken, $data) {
            return Http::productService()
                ->withToken($bearerToken)
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

        $reserveResponse = $this->circuitBreaker->call(function () use ($bearerToken, $data) {
            return Http::productService()
                ->withToken($bearerToken)
                ->post("/products/{$data['product_id']}/reserve", [
                    'quantity' => $data['quantity'],
                ]);
        });

        if ($reserveResponse instanceof \Illuminate\Http\JsonResponse) {
            throw new \RuntimeException('Service temporarily unavailable', 503);
        }
        if ($reserveResponse->failed()) {
            $err = $reserveResponse->json();
            throw new \RuntimeException('Failed to reserve stock: ' . json_encode($err), 400);
        }

        $order = $this->orderRepository->create([
            'user_id' => $data['user_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]);

        $this->publishOrderCreated($order);

        return $order;
    }

    private function publishOrderCreated(Order $order): void
    {
        try {
            $conf = new \RdKafka\Conf();
            $conf->set('metadata.broker.list', env('KAFKA_BROKERS', 'kafka:9092'));
            $producer = new \RdKafka\Producer($conf);
            $topic = $producer->newTopic('order.created');

            $payload = json_encode([
                'order_id' => (string) $order->id,
                'user_id' => (string) $order->user_id,
                'amount' => (float) $order->total_amount,
                'status' => 'pending',
            ]);

            $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload);
            $producer->poll(0);
            $producer->flush(10000);
        } catch (\Exception $e) {
            Log::error('Kafka Publish Failed: ' . $e->getMessage());
        }
    }
}
