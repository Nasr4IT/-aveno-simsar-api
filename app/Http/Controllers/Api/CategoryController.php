<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

/**
 * See docs/API_CONTRACT.md § Categories.
 * Read-only for the mobile app — categories/attributes are managed from
 * the admin panel (Api\Admin\AdminCategoryController).
 */
class CategoryController extends Controller
{
    // GET /api/categories — top-level categories with their children, for the home screen.
    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return CategoryResource::collection($categories);
    }

    // GET /api/categories/{category} — one category incl. its dynamic attributes,
    // used to render the "post an ad" form for that category.
    public function show(Category $category)
    {
        $category->load('attributes_', 'children');

        return new CategoryResource($category);
    }
}
