@extends('admin.layout')
@section('title', 'مراجعة الإعلانات')
@section('content')
    <div class="tabs">
        @foreach (['pending' => 'بانتظار المراجعة', 'approved' => 'منشورة', 'rejected' => 'مرفوضة', 'sold' => 'مباعة', '' => 'الكل'] as $value => $label)
            <a href="{{ route('admin.ads.index', $value ? ['status' => $value] : []) }}" class="{{ $status === $value ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card">
        <table>
            <thead>
                <tr><th>الإعلان</th><th>البائع</th><th>الفئة</th><th>السعر</th><th>الحالة</th><th>تاريخ النشر</th><th>إجراء</th></tr>
            </thead>
            <tbody>
                @forelse ($ads as $ad)
                    <tr>
                        <td>
                            @if ($ad->images->first())
                                <img class="thumb" src="{{ $ad->images->first()->url }}" alt="">
                            @endif
                            {{ $ad->title }}
                        </td>
                        <td>{{ $ad->user->name }}<br><span style="color:#9ca3af">{{ $ad->user->phone }}</span></td>
                        <td>{{ $ad->category->name_ar ?? '—' }}</td>
                        <td>{{ $ad->price !== null ? number_format($ad->price, 0).' '.$ad->currency : '—' }}</td>
                        <td><span class="badge badge-{{ $ad->status }}">{{ $ad->status }}</span></td>
                        <td>{{ $ad->created_at->format('Y-m-d') }}</td>
                        <td>
                            @if ($ad->status === 'pending')
                                <form class="inline" method="POST" action="{{ route('admin.ads.approve', $ad) }}">
                                    @csrf
                                    <button class="btn btn-primary" type="submit">موافقة</button>
                                </form>
                                <details class="reject-box">
                                    <summary>رفض</summary>
                                    <div class="card">
                                        <form method="POST" action="{{ route('admin.ads.reject', $ad) }}">
                                            @csrf
                                            <label>سبب الرفض</label>
                                            <textarea name="reason" rows="2" required></textarea>
                                            <button class="btn btn-danger" style="margin-top:8px" type="submit">تأكيد الرفض</button>
                                        </form>
                                    </div>
                                </details>
                            @else
                                <span style="color:#9ca3af">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="color:#9ca3af">لا توجد إعلانات.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $ads->links() }}</div>
@endsection
