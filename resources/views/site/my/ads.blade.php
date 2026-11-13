@extends('site.layout')
@section('title', 'إعلاناتي')
@section('content')
    @php
        $statusLabels = ['pending' => 'بانتظار المراجعة', 'approved' => 'منشور', 'rejected' => 'مرفوض', 'sold' => 'مُباع', 'expired' => 'منتهي'];
    @endphp
    <div class="section" style="margin-top:20px">
        <div class="section-head">
            <h1>إعلاناتي</h1>
            <a class="btn btn-primary btn-sm" href="{{ route('site.sell') }}">@include('site.partials.icon', ['name' => 'plus', 'size' => 16]) إعلان جديد</a>
        </div>

        <div class="cat-tiles" style="margin-bottom:20px">
            @foreach ([['list', $stats['total'], 'إجمالي الإعلانات'], ['eye', $stats['views'], 'مشاهدة'], ['heart', $stats['favorites'], 'في المفضلة'], ['chat', $stats['conversations'], 'محادثة']] as [$icon, $num, $label])
                <div class="cat-tile">
                    <div class="icon">@include('site.partials.icon', ['name' => $icon, 'size' => 22])</div>
                    <div><strong style="font-size:18px">{{ number_format($num) }}</strong><span>{{ $label }}</span></div>
                </div>
            @endforeach
        </div>

        @if ($ads->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'tag', 'size' => 40])
                لم تنشر أي إعلان بعد. <a href="{{ route('site.sell') }}">انشر أول إعلان لك</a>
            </div>
        @else
            <div class="card" style="padding:0; overflow:hidden">
                <table>
                    <thead><tr><th>الإعلان</th><th>الحالة</th><th>مشاهدات</th><th>مفضلة</th><th>محادثات</th><th>إجراءات</th></tr></thead>
                    <tbody>
                        @foreach ($ads as $ad)
                            @php $cover = $ad->images->firstWhere('is_cover', true) ?? $ad->images->first(); @endphp
                            <tr>
                                <td>
                                    <div class="row" style="flex-wrap:nowrap">
                                        @if ($cover)<img src="{{ $cover->url }}" alt="" style="width:56px;height:44px;object-fit:cover;border-radius:8px">@endif
                                        <div>
                                            @if (in_array($ad->status, ['approved', 'sold'], true))
                                                <a href="{{ route('site.ads.show', $ad) }}"><strong>{{ $ad->title }}</strong></a>
                                            @else
                                                <strong>{{ $ad->title }}</strong>
                                            @endif
                                            <div class="muted small">{{ $ad->price !== null ? number_format($ad->price, 0).' '.$ad->currency : 'بدون سعر' }} · {{ $ad->created_at->locale('ar')->diffForHumans() }}</div>
                                            @if ($ad->status === 'rejected' && $ad->rejection_reason)
                                                <div class="small" style="color:var(--danger)">سبب الرفض: {{ $ad->rejection_reason }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-{{ $ad->status }}">{{ $statusLabels[$ad->status] ?? $ad->status }}</span></td>
                                <td>{{ number_format($ad->views_count) }}</td>
                                <td>{{ $ad->favorited_by_count }}</td>
                                <td>{{ $ad->conversations_count }}</td>
                                <td>
                                    <div class="row">
                                        @if (in_array($ad->status, ['pending', 'approved', 'rejected'], true))
                                            <a class="btn btn-muted btn-sm" href="{{ route('site.my.ads.edit', $ad) }}">تعديل</a>
                                        @endif
                                        @if ($ad->status === 'approved')
                                            <form method="POST" action="{{ route('site.my.ads.mark-sold', $ad) }}">@csrf<button class="btn btn-outline btn-sm" type="submit">تم البيع</button></form>
                                        @endif
                                        @if (in_array($ad->status, ['sold', 'expired'], true))
                                            <form method="POST" action="{{ route('site.my.ads.relist', $ad) }}">@csrf<button class="btn btn-outline btn-sm" type="submit">إعادة النشر</button></form>
                                        @endif
                                        <form method="POST" action="{{ route('site.my.ads.destroy', $ad) }}" onsubmit="return confirm('حذف هذا الإعلان نهائيًا؟');">@csrf<button class="btn btn-danger btn-sm" type="submit">حذف</button></form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('site.partials.pagination', ['paginator' => $ads])
        @endif
    </div>
@endsection
