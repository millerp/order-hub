<?php

namespace App\Http\Controllers;

use App\Contracts\ProductServiceInterface;
use App\Http\Requests\ReserveProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    public function index(\Illuminate\Http\Request $request)
    {
        $products = $this->productService->getAll();

        return ProductResource::collection($products)->additional([
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
            ],
        ]);
    }

    public function show(\Illuminate\Http\Request $request, $id)
    {
        $product = $this->productService->getById((int) $id);

        return (new ProductResource($product))->additional([
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
            ],
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        Gate::authorize('create', Product::class);
        $product = $this->productService->create($request->validated());

        return response()->json([
            'data' => new ProductResource($product),
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
            ],
        ], 201);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        Gate::authorize('update', Product::class);
        $product = $this->productService->update((int) $id, $request->validated());

        return response()->json([
            'data' => new ProductResource($product),
            'meta' => [
                'request_id' => $request->attributes->get('request_id'),
            ],
        ]);
    }

    public function reserve(ReserveProductRequest $request, $id)
    {
        try {
            $validated = $request->validated();
            $result = $this->productService->reserveStock((int) $id, $validated['quantity']);

            if (! $result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'errors' => [
                        ['message' => $result['message']],
                    ],
                    'meta' => [
                        'request_id' => $request->attributes->get('request_id'),
                    ],
                ], 400);
            }

            return response()->json([
                'data' => [
                    'message' => $result['message'],
                    'remaining_stock' => $result['remaining_stock'],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
                'message' => $result['message'],
                'remaining_stock' => $result['remaining_stock'],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
                'errors' => [
                    ['message' => 'Product not found'],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Reservation failed',
                'errors' => [
                    [
                        'message' => 'Reservation failed',
                        'details' => $e->getMessage(),
                    ],
                ],
                'meta' => [
                    'request_id' => $request->attributes->get('request_id'),
                ],
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
