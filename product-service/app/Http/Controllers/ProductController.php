<?php

namespace App\Http\Controllers;

use App\Contracts\ProductServiceInterface;
use App\Http\Requests\ReserveProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductController extends Controller
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    public function index()
    {
        $products = $this->productService->getAll();

        return ProductResource::collection($products);
    }

    public function show($id)
    {
        $product = $this->productService->getById((int) $id);

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->productService->create($request->validated());

        return response()->json(new ProductResource($product), 201);
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $product = $this->productService->update((int) $id, $request->validated());

        return new ProductResource($product);
    }

    public function reserve(ReserveProductRequest $request, $id)
    {
        try {
            $validated = $request->validated();
            $result = $this->productService->reserveStock((int) $id, $validated['quantity']);

            if (! $result['success']) {
                return response()->json(['message' => $result['message']], 400);
            }

            return response()->json([
                'message' => $result['message'],
                'remaining_stock' => $result['remaining_stock'],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Reservation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
