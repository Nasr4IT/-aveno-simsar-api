@extends('admin.layout')
@section('title', __('الفئات'))
@section('content')
    @php
        $typeLabels = ['text' => __('نص'), 'number' => __('رقم'), 'boolean' => __('نعم/لا'), 'select' => __('اختيار واحد'), 'multiselect' => __('اختيار متعدد')];
    @endphp
    <div class="card">
        <h3 style="margin-top:0">{{ __('إضافة فئة جديدة') }}</h3>
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <label>{{ __('الاسم بالعربية') }}</label>
            <input type="text" name="name_ar" required>
            <label>{{ __('الاسم بالإنجليزية (اختياري)') }}</label>
            <input type="text" name="name_en">
            <label>{{ __('فئة رئيسية (اختياري)') }}</label>
            <select name="parent_id">
                <option value="">{{ __('— بدون —') }}</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->localized('name') }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary" type="submit" style="margin-top:12px">{{ __('إضافة') }}</button>
        </form>
    </div>

    @foreach ($categories as $category)
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
                <div>
                    <strong>{{ $category->localized('name') }}</strong>
                    <span class="muted">({{ $category->slug }})</span>
                    @if ($category->is_active)
                        <span class="badge badge-approved">{{ __('مفعّلة') }}</span>
                    @else
                        <span class="badge badge-rejected">{{ __('متوقفة') }}</span>
                    @endif
                    @if ($category->children->isNotEmpty())
                        <div class="muted" style="font-size:12.5px; margin-top:4px;">
                            {{ __('فئات فرعية: :list', ['list' => $category->children->map->localized('name')->join(__('، '))]) }}
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm({{ Js::from(__('حذف هذه الفئة؟')) }});">
                    @csrf
                    <button class="btn btn-danger" type="submit">{{ __('حذف') }}</button>
                </form>
            </div>

            <details style="margin-top:10px">
                <summary style="cursor:pointer; color:var(--teal-dark); font-size:13px;">{{ __('تعديل') }}</summary>
                <form method="POST" action="{{ route('admin.categories.update', $category) }}" style="margin-top:10px">
                    @csrf
                    <label>{{ __('الاسم بالعربية') }}</label>
                    <input type="text" name="name_ar" value="{{ $category->name_ar }}" required>
                    <label>{{ __('الاسم بالإنجليزية (اختياري)') }}</label>
                    <input type="text" name="name_en" value="{{ $category->name_en }}">
                    <label>{{ __('ترتيب العرض') }}</label>
                    <input type="number" name="sort_order" value="{{ $category->sort_order }}">
                    <label><input type="checkbox" name="is_active" value="1" style="width:auto" {{ $category->is_active ? 'checked' : '' }}> {{ __('مفعّلة (تظهر للمستخدمين ويمكن نشر إعلانات جديدة فيها)') }}</label>
                    <button class="btn btn-primary" type="submit" style="margin-top:10px">{{ __('حفظ التعديلات') }}</button>
                </form>
            </details>

            @if ($category->attributes_->isNotEmpty())
                <div class="table-wrap">
                    <table style="margin-top:12px">
                        <thead><tr><th>{{ __('المفتاح') }}</th><th>{{ __('التسمية') }}</th><th>{{ __('النوع') }}</th><th>{{ __('الخيارات') }}</th><th>{{ __('مطلوب') }}</th><th>{{ __('قابل للفلترة') }}</th></tr></thead>
                        <tbody>
                            @foreach ($category->attributes_ as $attr)
                                <tr>
                                    <td dir="ltr" style="text-align:start">{{ $attr->key }}</td>
                                    <td>{{ $attr->localized('label') }}</td>
                                    <td>{{ $typeLabels[$attr->type] ?? $attr->type }}</td>
                                    <td>{{ $attr->options ? implode(__('، '), $attr->options) : '—' }}</td>
                                    <td>{{ $attr->is_required ? __('نعم') : __('لا') }}</td>
                                    <td>{{ $attr->is_filterable ? __('نعم') : __('لا') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <details style="margin-top:10px">
                <summary style="cursor:pointer; color:var(--teal-dark); font-size:13px;">{{ __('+ إضافة خاصية ديناميكية') }}</summary>
                <form method="POST" action="{{ route('admin.categories.attributes.store', $category) }}" style="margin-top:10px">
                    @csrf
                    <label>{{ __('المفتاح (بالإنجليزية، بدون مسافات)') }}</label>
                    <input type="text" name="key" required dir="ltr">
                    <label>{{ __('التسمية بالعربية') }}</label>
                    <input type="text" name="label_ar" required>
                    <label>{{ __('التسمية بالإنجليزية (اختياري)') }}</label>
                    <input type="text" name="label_en" dir="ltr">
                    <label>{{ __('النوع') }}</label>
                    <select name="type" required>
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <label>{{ __('الخيارات (مفصولة بفاصلة — لنوع الاختيار فقط)') }}</label>
                    <input type="text" name="options" placeholder="{{ __('بنزين، ديزل، كهربائي') }}">
                    <label><input type="checkbox" name="is_required" value="1" style="width:auto"> {{ __('مطلوب') }}</label>
                    <label><input type="checkbox" name="is_filterable" value="1" style="width:auto"> {{ __('قابل للفلترة في البحث') }}</label>
                    <button class="btn btn-primary" type="submit" style="margin-top:10px">{{ __('إضافة الخاصية') }}</button>
                </form>
            </details>
        </div>
    @endforeach
@endsection
