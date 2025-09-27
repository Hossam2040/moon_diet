<?php

namespace App\Http\Controllers;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuAdminController extends Controller
{
    // Categories
    public function indexCategories(Request $request)
    {
        try {
            $query = MenuCategory::query()->orderBy('name_en');
            $shouldPaginate = $request->boolean('paginate', false) || $request->has('per_page');
            $onlyData = $request->boolean('only_data', true);
            if ($shouldPaginate) {
                $perPage = (int) ($request->query('per_page', 15));
                $paginator = $query->paginate($perPage);
                if ($onlyData) {
                    return response()->json(['data' => $paginator->items()]);
                }
                return response()->json($paginator);
            }
            $collection = $query->get();
            return response()->json(['data' => $collection]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to list categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'name_en' => ['required', 'string', 'max:255'],
                'name_ar' => ['required', 'string', 'max:255'],
                'description_en' => ['nullable', 'string'],
                'description_ar' => ['nullable', 'string'],
            ]);
            $category = MenuCategory::create($validated);
            return response()->json($category, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showCategory(int $id)
    {
        try {
            $category = MenuCategory::findOrFail($id);
            return response()->json($category);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateCategory(Request $request, int $id)
    {
        try {
            $category = MenuCategory::findOrFail($id);
            $validated = $request->validate([
                'name_en' => ['sometimes', 'string', 'max:255'],
                'name_ar' => ['sometimes', 'string', 'max:255'],
                'description_en' => ['sometimes', 'nullable', 'string'],
                'description_ar' => ['sometimes', 'nullable', 'string'],
            ]);
            $category->fill($validated)->save();
            $category->refresh();
            return response()->json($category);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyCategory(int $id)
    {
        try {
            $category = MenuCategory::findOrFail($id);
            $category->delete();
            return response()->json(['deleted' => true]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Category not found'], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete category',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Items
    public function indexItems(Request $request)
    {
        try {
            $categoryId = $request->query('category_id');
            $query = MenuItem::query()->orderBy('name_en');
            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }
            $shouldPaginate = $request->boolean('paginate', false) || $request->has('per_page');
            $onlyData = $request->boolean('only_data', true);
            if ($shouldPaginate) {
                $perPage = (int) ($request->query('per_page', 15));
                $paginator = $query->paginate($perPage);
                if ($onlyData) {
                    return response()->json(['data' => $paginator->items()]);
                }
                return response()->json($paginator);
            }
            $collection = $query->get();
            return response()->json(['data' => $collection]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to list items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeItem(Request $request)
    {
        try {
            if ($request->has('object_reason') && is_string($request->input('object_reason'))) {
                $decoded = json_decode($request->input('object_reason'), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge(['object_reason' => $decoded]);
                }
            }
            $validated = $request->validate([
                'category_id' => ['required', 'integer', Rule::exists('menu_categories', 'id')],
                'name_en' => ['required', 'string', 'max:255'],
                'name_ar' => ['required', 'string', 'max:255'],
                'description_en' => ['nullable', 'string'],
                'description_ar' => ['nullable', 'string'],
                'price' => ['required', 'numeric', 'min:0'],
                'calories' => ['nullable', 'integer', 'min:0'],
                'protein' => ['nullable', 'integer', 'min:0'],
                'carb' => ['nullable', 'integer', 'min:0'],
                'fat' => ['nullable', 'integer', 'min:0'],
                'image_url' => ['nullable', 'url'],
                'object_reason' => ['nullable', 'array'],
                'object_reason.reason' => ['nullable', 'string'],
                'object_reason.icon' => ['nullable', 'string'],
            ]);
            $item = MenuItem::create($validated);
            return response()->json($item, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showItem(int $id)
    {
        try {
            $item = MenuItem::findOrFail($id);
            return response()->json($item);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Item not found'], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to fetch item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateItem(Request $request, int $id)
    {
        try {
            $item = MenuItem::findOrFail($id);
            if ($request->has('object_reason') && is_string($request->input('object_reason'))) {
                $decoded = json_decode($request->input('object_reason'), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $request->merge(['object_reason' => $decoded]);
                }
            }
            $validated = $request->validate([
                'category_id' => ['sometimes', 'integer', Rule::exists('menu_categories', 'id')],
                'name_en' => ['sometimes', 'string', 'max:255'],
                'name_ar' => ['sometimes', 'string', 'max:255'],
                'description_en' => ['sometimes', 'nullable', 'string'],
                'description_ar' => ['sometimes', 'nullable', 'string'],
                'price' => ['sometimes', 'numeric', 'min:0'],
                'calories' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'protein' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'carb' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'fat' => ['sometimes', 'nullable', 'integer', 'min:0'],
                'image_url' => ['sometimes', 'nullable', 'url'],
                'object_reason' => ['sometimes', 'nullable', 'array'],
                'object_reason.reason' => ['sometimes', 'nullable', 'string'],
                'object_reason.icon' => ['sometimes', 'nullable', 'string'],
            ]);
            $item->fill($validated)->save();
            $item->refresh();
            return response()->json($item);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Item not found'], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to update item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyItem(int $id)
    {
        try {
            $item = MenuItem::findOrFail($id);
            $item->delete();
            return response()->json(['deleted' => true]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Item not found'], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to delete item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}


