<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryAttributeRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

// See docs/API_CONTRACT.md § Admin ▸ Categories. All routes require the "admin" middleware.
class AdminCategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(Category::with('children', 'attributes_')->orderBy('sort_order')->get());
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return new CategoryResource($category);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'name_ar' => ['sometimes', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]));

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        // category_attributes and child categories cascade-delete at the DB
        // level, but ads.category_id has no ON DELETE rule — deleting a
        // category (or a parent whose cascaded-deleted child) that still has
        // ads hits a foreign key violation. Catch it here rather than
        // leaking a raw SQL error/stack trace to the client.
        try {
            $category->delete();
        } catch (QueryException $e) {
            abort(409, 'لا يمكن حذف هذه الفئة لوجود إعلانات مرتبطة بها أو بإحدى فئاتها الفرعية.');
        }

        return response()->json(['message' => 'تم الحذف']);
    }

    // POST /api/admin/categories/{category}/attributes — add a dynamic spec field.
    public function storeAttribute(StoreCategoryAttributeRequest $request, Category $category)
    {
        $attribute = $category->attributes_()->create($request->validated());

        return response()->json($attribute, 201);
    }
}
