<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::active()
            ->with('category')
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->input('category_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->input('search')}%"))
            ->orderBy('name')
            ->get();

        return response()->json(['products' => $products]);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load('category');

        return response()->json(['product' => $product]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->safe()->except(['image']);
        $data['image'] = $this->resolveImage($request);

        $product = Product::create($data);

        ActivityLog::record($request->user(), 'product_created', ['product_id' => $product->id, 'name' => $product->name]);

        return response()->json(['product' => $product], 201);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $data = $request->safe()->except(['image']);
        $data['image'] = $this->resolveImage($request, $product);

        $product->update($data);

        ActivityLog::record($request->user(), 'product_updated', ['product_id' => $product->id, 'name' => $product->name]);

        return response()->json(['product' => $product->fresh()]);
    }

public function destroy(Product $product): JsonResponse
    {
        $product->update(['active' => false]);

        ActivityLog::record(request()->user(), 'product_deleted', ['product_id' => $product->id, 'name' => $product->name]);

        return response()->json(['message' => 'Produit dǸsactivǸ.', 'product' => $product->fresh()]);
    }

    private function resolveImage(Request $request, ?Product $product = null): ?string
    {
        if ($request->hasFile('image')) {
            $request->validate(['image' => ['image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120']]);

            return $request->file('image')->store('products', 'public');
        }

        if ($request->boolean('remove_image')) {
            return null;
        }

        if ($request->filled('image')) {
            return (string) $request->input('image');
        }

        return $product?->image;
    }
}
