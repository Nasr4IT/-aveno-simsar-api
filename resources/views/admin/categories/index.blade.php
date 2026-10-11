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
            <label>ترتيب العرض (اختياري، الأصغر يظهر أولاً)</label>
            <input type="number" name="sort_order" min="0" value="0">
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
                    <span style="color:#9ca3af">({{ $category->slug }})</span>
                    @if ($category->children->isNotEmpty())
                        <div style="color:#9ca3af; font-size:12.5px; margin-top:4px;">
                            فئات فرعية: {{ $category->children->pluck('name_ar')->join('، ') }}
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('حذف هذه الفئة؟');">
                    @csrf
                    <button class="btn btn-danger" type="submit">حذف</button>
                </form>
            </div>

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
                <summary style="cursor:pointer; color:var(--primary); font-size:13px;">+ إضافة خاصية ديناميكية</summary>
                <form method="POST" action="{{ route('admin.categories.attributes.store', $category) }}" style="margin-top:10px">
                    @csrf
                    <label>المفتاح (أحرف إنجليزية صغيرة وأرقام و _ فقط، مثل fuel_type)</label>
                    <input type="text" name="key" required pattern="[a-z][a-z0-9_]*" maxlength="60" dir="ltr">
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
                    <label>الخيارات (مفصولة بفاصلة — مطلوبة لنوع الاختيار، ويُتجاهل لغيره)</label>
                    <input type="text" name="options" placeholder="بنزين، ديزل، كهربائي">
                    {{-- The hidden 0 makes an unticked box send false explicitly, like the API's JSON boolean. --}}
                    <input type="hidden" name="is_required" value="0">
                    <label><input type="checkbox" name="is_required" value="1" style="width:auto"> مطلوب</label>
                    <input type="hidden" name="is_filterable" value="0">
                    <label><input type="checkbox" name="is_filterable" value="1" style="width:auto" checked> قابل للفلترة في البحث</label>
                    <button class="btn btn-primary" type="submit" style="margin-top:10px">إضافة الخاصية</button>
                </form>
            </details>
        </div>
    @endforeach
@endsection
