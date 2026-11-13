@extends('admin.layout')
@section('title', __('الرئيسية'))
@section('content')
    <div class="stat-grid">
        @foreach ([
            ['ads', $pendingAds, __('إعلانات بانتظار المراجعة'), route('admin.ads.index')],
            ['users', $totalUsers, __('إجمالي المستخدمين'), route('admin.users.index')],
            ['users', $bannedUsers, __('مستخدمون محظورون'), null],
            ['flag', $pendingReports, __('بلاغات بانتظار المعالجة'), route('admin.reports.index')],
            ['image', $activeBanners, __('بانرات نشطة حالياً'), route('admin.banners.index')],
        ] as [$icon, $num, $label, $link])
            <div class="card">
                <div class="icon-badge">@include('admin.partials.icon', ['name' => $icon])</div>
                <div class="num" data-count="{{ $num }}">{{ number_format($num) }}</div>
                <div class="label">{{ $label }}</div>
                @if ($link)
                    <a href="{{ $link }}">{{ __('عرض') }} {{ app()->getLocale() === 'ar' ? '‹' : '›' }}</a>
                @endif
            </div>
        @endforeach
    </div>
@endsection
