<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryAttributeRequest;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;

class CategoryManagementController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::with('children', 'attributes_')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        Category::create($request->validated());

        return back()->with('status', 'تمت إضافة الفئة.');
    }

    // Same request class as the API's storeAttribute(), which also turns
    // the form's comma-separated options string into an array.
    public function storeAttribute(StoreCategoryAttributeRequest $request, Category $category)
    {
        $category->attributes_()->create($request->validated());

        return back()->with('status', 'تمت إضافة الخاصية.');
    }

    public function destroy(Category $category)
    {
        try {
            $category->delete();
        } catch (QueryException $e) {
            return back()->withErrors(['category' => 'لا يمكن حذف هذه الفئة لوجود إعلانات مرتبطة بها أو بإحدى فئاتها الفرعية.']);
        }

        return back()->with('status', 'تم الحذف.');
    }
}
