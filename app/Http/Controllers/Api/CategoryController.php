<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount(['products as products_count' => fn ($q) => $q->where('active', true)])
            ->orderBy('label')
            ->get();

        return response()->json(['categories' => $categories]);
    }

    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        ActivityLog::record($request->user(), 'category_created', ['category_id' => $category->id, 'label' => $category->label]);

        return response()->json(['category' => $category], 201);
    }

    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $category->update($request->validated());

        ActivityLog::record($request->user(), 'category_updated', ['category_id' => $category->id, 'label' => $category->label]);

        return response()->json(['category' => $category->fresh()]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $hasActiveProducts = $category->products()->where('active', true)->exists();

        if ($hasActiveProducts) {
            return response()->json([
                'message' => 'Impossible de supprimer : la catégorie contient des produits actifs.',
            ], 409);
        }

        $category->delete();

        ActivityLog::record(request()->user(), 'category_deleted', ['category_id' => $category->id, 'label' => $category->label]);

        return response()->json(['message' => 'Catégorie supprimée.']);
    }
}
