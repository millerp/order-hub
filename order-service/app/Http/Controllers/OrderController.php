<?php

namespace App\Http\Controllers;

use App\Contracts\OrderServiceInterface;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{
    public function __construct(
        private OrderServiceInterface $orderService
    ) {
    }

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
            return response()->json(new OrderResource($order), 201);
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            $status = is_int($code) && $code >= 400 ? $code : 500;
            $message = $e->getCode() === 503 ? 'Service temporarily unavailable' : $e->getMessage();
            return response()->json(['message' => $message], $status);
        }
    }
}
