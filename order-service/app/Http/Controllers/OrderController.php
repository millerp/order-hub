<?php

namespace App\Http\Controllers;

use App\Contracts\OrderServiceInterface;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderServiceInterface $orderService
    ) {}

    public function index(Request $request)
    {
        $orders = $this->orderService->getOrdersByUser((string) $request->user()->id);

        return OrderResource::collection($orders)->additional([
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
                'trace_id' => $request->attributes->get('trace_id'),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;
        $traceId = (string) $request->attributes->get('trace_id');

        try {
            $order = $this->orderService->createOrder($validated, $request->bearerToken(), $traceId);

            return response()->json([
                'data' => new OrderResource($order),
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                    'trace_id' => $traceId,
                ],
            ], 201);
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            $status = is_int($code) && $code >= 400 ? $code : 500;
            $message = $e->getCode() === 503 ? 'Service temporarily unavailable' : $e->getMessage();

            return response()->json([
                'message' => $message,
                'errors' => [
                    ['message' => $message],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                    'trace_id' => $traceId,
                ],
            ], $status);
        }
    }

    public function stream(Request $request)
    {
        $userId = (string) $request->user()->id;
        $requestId = (string) $request->attributes->get('request_id');
        $traceId = (string) $request->attributes->get('trace_id');
        $maxIterations = max(1, min(20, (int) $request->query('max_iterations', 20)));

        return response()->stream(function () use ($userId, $requestId, $traceId, $maxIterations) {
            $lastHash = null;

            for ($i = 0; $i < $maxIterations; $i++) {
                if (connection_aborted()) {
                    break;
                }

                $orders = $this->orderService->getOrdersByUser($userId)->map(fn ($order) => [
                    'id' => $order->id,
                    'product_id' => $order->product_id,
                    'quantity' => $order->quantity,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'created_at' => $order->created_at,
                ])->values();

                $payload = [
                    'data' => $orders,
                    'meta' => [
                        'request_id' => $requestId,
                        'trace_id' => $traceId,
                        'emitted_at' => now()->toIso8601String(),
                    ],
                ];
                $json = json_encode($payload);
                $hash = md5((string) $json);

                if ($hash !== $lastHash) {
                    echo "event: orders\n";
                    echo "data: {$json}\n\n";
                    $lastHash = $hash;

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
