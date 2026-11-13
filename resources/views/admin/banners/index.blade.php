@extends('admin.layout')
@section('title', __('البانرات'))
@section('content')
    <div class="card">
        <h3 style="margin-top:0">{{ __('إضافة بانر جديد') }}</h3>
        <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
            @csrf
            <label>{{ __('الصورة') }}</label>
            <input type="file" name="image" accept="image/*" required>
            <label>{{ __('العنوان (اختياري)') }}</label>
            <input type="text" name="title">
            <label>{{ __('الرابط عند الضغط (اختياري — https:// أو tel:)') }}</label>
            <input type="text" name="link_url" placeholder="https://example.com · tel:+963911111111" dir="ltr">
            <label>{{ __('تاريخ البدء') }}</label>
            <input type="datetime-local" name="starts_at" required>
            <label>{{ __('تاريخ الانتهاء') }}</label>
            <input type="datetime-local" name="ends_at" required>
            <label>{{ __('ترتيب العرض (اختياري، الأصغر يظهر أولاً)') }}</label>
            <input type="number" name="sort_order" min="0" value="0">
            <button class="btn btn-primary" type="submit" style="margin-top:12px">{{ __('إضافة') }}</button>
        </form>
    </div>

    <div class="card table-wrap">
        <table>
            <thead><tr><th>{{ __('الصورة') }}</th><th>{{ __('العنوان') }}</th><th>{{ __('الفترة') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('إجراء') }}</th></tr></thead>
            <tbody>
                @forelse ($banners as $banner)
                    <tr>
                        <td><img class="thumb" src="{{ $banner->image_url }}" alt=""></td>
                        <td>{{ $banner->title ?? '—' }}</td>
                        <td style="font-size:12px" dir="ltr">{{ $banner->starts_at->format('Y-m-d') }} → {{ $banner->ends_at->format('Y-m-d') }}</td>
                        <td>
                            @if ($banner->is_active)
                                <span class="badge badge-approved">{{ __('مفعّل') }}</span>
                            @else
                                <span class="badge badge-rejected">{{ __('متوقف') }}</span>
                            @endif
                        </td>
                        <td>
                            <form class="inline" method="POST" action="{{ route('admin.banners.toggle', $banner) }}">
                                @csrf
                                <button class="btn btn-muted" type="submit">{{ $banner->is_active ? __('إيقاف') : __('تفعيل') }}</button>
                            </form>
                            <form class="inline" method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm({{ Js::from(__('حذف هذا البانر؟')) }});">
                                @csrf
                                <button class="btn btn-danger" type="submit">{{ __('حذف') }}</button>
                            </form>
                            <details style="margin-top:8px">
                                <summary style="cursor:pointer; color:var(--teal-dark); font-size:13px;">{{ __('تعديل') }}</summary>
                                <form method="POST" action="{{ route('admin.banners.update', $banner) }}" style="margin-top:10px; min-width:220px;">
                                    @csrf
                                    <label>{{ __('العنوان (اختياري)') }}</label>
                                    <input type="text" name="title" value="{{ $banner->title }}">
                                    <label>{{ __('الرابط عند الضغط (اختياري — https:// أو tel:)') }}</label>
                                    <input type="text" name="link_url" value="{{ $banner->link_url }}" dir="ltr">
                                    <label>{{ __('تاريخ البدء') }}</label>
                                    <input type="datetime-local" name="starts_at" value="{{ $banner->starts_at->format('Y-m-d\TH:i') }}" required>
                                    <label>{{ __('تاريخ الانتهاء') }}</label>
                                    <input type="datetime-local" name="ends_at" value="{{ $banner->ends_at->format('Y-m-d\TH:i') }}" required>
                                    <label>{{ __('ترتيب العرض') }}</label>
                                    <input type="number" name="sort_order" min="0" value="{{ $banner->sort_order }}">
                                    <button class="btn btn-primary" type="submit" style="margin-top:10px">{{ __('حفظ التعديلات') }}</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">{{ __('لا توجد بانرات.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('site.partials.pagination', ['paginator' => $banners])
@endsection
