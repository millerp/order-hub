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

        return OrderResource::collection($orders);
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        $validated['user_id'] = $request->user()->id;

        try {
            $order = $this->orderService->createOrder($validated, $request->bearerToken());

            return response()->json([
                'data' => new OrderResource($order),
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
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
                ],
            ], $status);
        }
    }
}
