<?php

namespace App\Http\Controllers\AdminPanel;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryManagementController extends Controller
{
    public function index()
    {
        return view('admin.categories.index', [
            'categories' => Category::with('children', 'attributes_')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
        ]);

        Category::create([...$data, 'slug' => Str::slug($data['name_en'] ?? $data['name_ar']).'-'.Str::random(4)]);

        return back()->with('status', 'تمت إضافة الفئة.');
    }

    // A select/multiselect's choices come in as a comma-separated string
    // from the plain HTML form field — split into the array the attribute
    // model actually stores (same shape the API's storeAttribute() takes
    // as a JSON array).
    public function storeAttribute(Request $request, Category $category)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:60'],
            'label_ar' => ['required', 'string', 'max:100'],
            'label_en' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'in:text,number,boolean,select,multiselect'],
            'options' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
        ]);

        $category->attributes_()->create([
            ...$data,
            'options' => $data['options'] ? array_map('trim', explode(',', $data['options'])) : null,
            'is_required' => $request->boolean('is_required'),
            'is_filterable' => $request->boolean('is_filterable'),
        ]);

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
