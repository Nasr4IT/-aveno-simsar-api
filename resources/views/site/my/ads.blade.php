@extends('site.layout')
@section('title', __('إعلاناتي'))
@section('content')
    @php
        $statusLabels = ['pending' => __('بانتظار المراجعة'), 'approved' => __('منشور'), 'rejected' => __('مرفوض'), 'sold' => __('مُباع'), 'expired' => __('منتهي')];
    @endphp
    <div class="section" style="margin-top:20px">
        <div class="section-head">
            <h1>{{ __('إعلاناتي') }}</h1>
            <a class="btn btn-primary btn-sm" href="{{ route('site.sell') }}">@include('site.partials.icon', ['name' => 'plus', 'size' => 16]) {{ __('إعلان جديد') }}</a>
        </div>

        <div class="cat-tiles" style="margin-bottom:20px">
            @foreach ([['list', $stats['total'], __('إجمالي الإعلانات')], ['eye', $stats['views'], __('مشاهدة')], ['heart', $stats['favorites'], __('في المفضلة')], ['chat', $stats['conversations'], __('محادثة')]] as [$icon, $num, $label])
                <div class="cat-tile">
                    <div class="icon">@include('site.partials.icon', ['name' => $icon, 'size' => 22])</div>
                    <div><strong style="font-size:18px" data-count="{{ $num }}">{{ number_format($num) }}</strong><span>{{ $label }}</span></div>
                </div>
            @endforeach
        </div>

        @if ($ads->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'tag', 'size' => 40])
                {{ __('لم تنشر أي إعلان بعد.') }} <a href="{{ route('site.sell') }}">{{ __('انشر أول إعلان لك') }}</a>
            </div>
        @else
            <div class="card table-wrap" style="padding:0">
                <table>
                    <thead><tr><th>{{ __('الإعلان') }}</th><th>{{ __('الحالة') }}</th><th>{{ __('مشاهدات') }}</th><th>{{ __('مفضلة') }}</th><th>{{ __('محادثات') }}</th><th>{{ __('إجراءات') }}</th></tr></thead>
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
                                            <div class="muted small">{{ $ad->price !== null ? number_format($ad->price, 0).' '.$ad->currency : __('بدون سعر') }} · {{ $ad->created_at->diffForHumans() }}</div>
                                            @if ($ad->status === 'rejected' && $ad->rejection_reason)
                                                <div class="small" style="color:var(--danger)">{{ __('سبب الرفض: :reason', ['reason' => $ad->rejection_reason]) }}</div>
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
                                            <a class="btn btn-muted btn-sm" href="{{ route('site.my.ads.edit', $ad) }}">{{ __('تعديل') }}</a>
                                        @endif
                                        @if ($ad->status === 'approved')
                                            <form method="POST" action="{{ route('site.my.ads.mark-sold', $ad) }}">@csrf<button class="btn btn-outline btn-sm" type="submit">{{ __('تم البيع') }}</button></form>
                                        @endif
                                        @if (in_array($ad->status, ['sold', 'expired'], true))
                                            <form method="POST" action="{{ route('site.my.ads.relist', $ad) }}">@csrf<button class="btn btn-outline btn-sm" type="submit">{{ __('إعادة النشر') }}</button></form>
                                        @endif
                                        <form method="POST" action="{{ route('site.my.ads.destroy', $ad) }}" onsubmit="return confirm({{ Js::from(__('حذف هذا الإعلان نهائيًا؟')) }});">@csrf<button class="btn btn-danger btn-sm" type="submit">{{ __('حذف') }}</button></form>
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

    @push('scripts')
        <script>
            // Stats count up from zero on first view.
            if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.querySelectorAll('[data-count]').forEach(function (el) {
                    var target = parseInt(el.dataset.count, 10) || 0, start = null, fmt = new Intl.NumberFormat('en-US');
                    if (target < 2) return;
                    function step(ts) {
                        if (!start) start = ts;
                        var p = Math.min((ts - start) / 900, 1), eased = 1 - Math.pow(1 - p, 3);
                        el.textContent = fmt.format(Math.round(target * eased));
                        if (p < 1) requestAnimationFrame(step);
                    }
                    requestAnimationFrame(step);
                });
            }
        </script>
    @endpush
@endsection
