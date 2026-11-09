@extends('admin.layout')
@section('title', 'البانرات')
@section('content')
    <div class="card">
        <h3 style="margin-top:0">إضافة بانر جديد</h3>
        <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
            @csrf
            <label>الصورة</label>
            <input type="file" name="image" accept="image/*" required>
            <label>العنوان (اختياري)</label>
            <input type="text" name="title">
            <label>الرابط عند الضغط (اختياري — https:// أو tel:)</label>
            <input type="text" name="link_url" placeholder="https://example.com أو tel:+963911111111">
            <label>تاريخ البدء</label>
            <input type="datetime-local" name="starts_at" required>
            <label>تاريخ الانتهاء</label>
            <input type="datetime-local" name="ends_at" required>
            <label>ترتيب العرض (اختياري، الأصغر يظهر أولاً)</label>
            <input type="number" name="sort_order" min="0" value="0">
            <button class="btn btn-primary" type="submit" style="margin-top:12px">إضافة</button>
        </form>
    </div>

    <div class="card">
        <table>
            <thead><tr><th>الصورة</th><th>العنوان</th><th>الفترة</th><th>الحالة</th><th>إجراء</th></tr></thead>
            <tbody>
                @forelse ($banners as $banner)
                    <tr>
                        <td><img class="thumb" src="{{ $banner->image_url }}" alt=""></td>
                        <td>{{ $banner->title ?? '—' }}</td>
                        <td style="font-size:12px">{{ $banner->starts_at->format('Y-m-d') }} → {{ $banner->ends_at->format('Y-m-d') }}</td>
                        <td>
                            @if ($banner->is_active)
                                <span class="badge badge-approved">مفعّل</span>
                            @else
                                <span class="badge badge-rejected">متوقف</span>
                            @endif
                        </td>
                        <td>
                            <form class="inline" method="POST" action="{{ route('admin.banners.toggle', $banner) }}">
                                @csrf
                                <button class="btn btn-muted" type="submit">{{ $banner->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                            </form>
                            <form class="inline" method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('حذف هذا البانر؟');">
                                @csrf
                                <button class="btn btn-danger" type="submit">حذف</button>
                            </form>
                            <details style="margin-top:8px">
                                <summary style="cursor:pointer; color:var(--teal-dark); font-size:13px;">تعديل</summary>
                                <form method="POST" action="{{ route('admin.banners.update', $banner) }}" style="margin-top:10px; min-width:220px;">
                                    @csrf
                                    <label>العنوان (اختياري)</label>
                                    <input type="text" name="title" value="{{ $banner->title }}">
                                    <label>الرابط عند الضغط (اختياري — https:// أو tel:)</label>
                                    <input type="text" name="link_url" value="{{ $banner->link_url }}">
                                    <label>تاريخ البدء</label>
                                    <input type="datetime-local" name="starts_at" value="{{ $banner->starts_at->format('Y-m-d\TH:i') }}" required>
                                    <label>تاريخ الانتهاء</label>
                                    <input type="datetime-local" name="ends_at" value="{{ $banner->ends_at->format('Y-m-d\TH:i') }}" required>
                                    <label>ترتيب العرض</label>
                                    <input type="number" name="sort_order" min="0" value="{{ $banner->sort_order }}">
                                    <button class="btn btn-primary" type="submit" style="margin-top:10px">حفظ التعديلات</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:var(--muted)">لا توجد بانرات.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $banners->links() }}</div>
@endsection
