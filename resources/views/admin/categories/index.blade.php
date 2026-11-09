@extends('admin.layout')
@section('title', 'الفئات')
@section('content')
    <div class="card">
        <h3 style="margin-top:0">إضافة فئة جديدة</h3>
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <label>الاسم بالعربية</label>
            <input type="text" name="name_ar" required>
            <label>الاسم بالإنجليزية (اختياري)</label>
            <input type="text" name="name_en">
            <label>فئة رئيسية (اختياري)</label>
            <select name="parent_id">
                <option value="">— بدون —</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->name_ar }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit" style="margin-top:12px">إضافة</button>
        </form>
    </div>

    @foreach ($categories as $category)
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <strong>{{ $category->name_ar }}</strong>
                    <span style="color:var(--muted)">({{ $category->slug }})</span>
                    @if ($category->is_active)
                        <span class="badge badge-approved">مفعّلة</span>
                    @else
                        <span class="badge badge-rejected">متوقفة</span>
                    @endif
                    @if ($category->children->isNotEmpty())
                        <div style="color:var(--muted); font-size:12.5px; margin-top:4px;">
                            فئات فرعية: {{ $category->children->pluck('name_ar')->join('، ') }}
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('حذف هذه الفئة؟');">
                    @csrf
                    <button class="btn btn-danger" type="submit">حذف</button>
                </form>
            </div>

            <details style="margin-top:10px">
                <summary style="cursor:pointer; color:var(--teal-dark); font-size:13px;">تعديل</summary>
                <form method="POST" action="{{ route('admin.categories.update', $category) }}" style="margin-top:10px">
                    @csrf
                    <label>الاسم بالعربية</label>
                    <input type="text" name="name_ar" value="{{ $category->name_ar }}" required>
                    <label>الاسم بالإنجليزية (اختياري)</label>
                    <input type="text" name="name_en" value="{{ $category->name_en }}">
                    <label>ترتيب العرض</label>
                    <input type="number" name="sort_order" value="{{ $category->sort_order }}">
                    <label><input type="checkbox" name="is_active" value="1" style="width:auto" {{ $category->is_active ? 'checked' : '' }}> مفعّلة (تظهر للمستخدمين ويمكن نشر إعلانات جديدة فيها)</label>
                    <button class="btn btn-primary" type="submit" style="margin-top:10px">حفظ التعديلات</button>
                </form>
            </details>

            @if ($category->attributes_->isNotEmpty())
                <table style="margin-top:12px">
                    <thead><tr><th>المفتاح</th><th>التسمية</th><th>النوع</th><th>الخيارات</th><th>مطلوب</th><th>قابل للفلترة</th></tr></thead>
                    <tbody>
                        @foreach ($category->attributes_ as $attr)
                            <tr>
                                <td>{{ $attr->key }}</td>
                                <td>{{ $attr->label_ar }}</td>
                                <td>{{ $attr->type }}</td>
                                <td>{{ $attr->options ? implode('، ', $attr->options) : '—' }}</td>
                                <td>{{ $attr->is_required ? 'نعم' : 'لا' }}</td>
                                <td>{{ $attr->is_filterable ? 'نعم' : 'لا' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <details style="margin-top:10px">
                <summary style="cursor:pointer; color:var(--teal-dark); font-size:13px;">+ إضافة خاصية ديناميكية</summary>
                <form method="POST" action="{{ route('admin.categories.attributes.store', $category) }}" style="margin-top:10px">
                    @csrf
                    <label>المفتاح (بالإنجليزية، بدون مسافات)</label>
                    <input type="text" name="key" required>
                    <label>التسمية بالعربية</label>
                    <input type="text" name="label_ar" required>
                    <label>النوع</label>
                    <select name="type" required>
                        <option value="text">نص</option>
                        <option value="number">رقم</option>
                        <option value="boolean">نعم/لا</option>
                        <option value="select">اختيار واحد</option>
                        <option value="multiselect">اختيار متعدد</option>
                    </select>
                    <label>الخيارات (مفصولة بفاصلة — لنوع الاختيار فقط)</label>
                    <input type="text" name="options" placeholder="بنزين، ديزل، كهربائي">
                    <label><input type="checkbox" name="is_required" value="1" style="width:auto"> مطلوب</label>
                    <label><input type="checkbox" name="is_filterable" value="1" style="width:auto"> قابل للفلترة في البحث</label>
                    <button class="btn btn-primary" type="submit" style="margin-top:10px">إضافة الخاصية</button>
                </form>
            </details>
        </div>
    @endforeach
@endsection
