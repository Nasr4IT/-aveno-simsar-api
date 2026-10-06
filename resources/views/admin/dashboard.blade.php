@extends('admin.layout')
@section('title', 'الرئيسية')
@section('content')
    <div class="stat-grid">
        <div class="card">
            <div class="num">{{ $pendingAds }}</div>
            <div class="label">إعلانات بانتظار المراجعة</div>
            <a href="{{ route('admin.ads.index') }}">عرض ›</a>
        </div>
        <div class="card">
            <div class="num">{{ $totalUsers }}</div>
            <div class="label">إجمالي المستخدمين</div>
            <a href="{{ route('admin.users.index') }}">عرض ›</a>
        </div>
        <div class="card">
            <div class="num">{{ $bannedUsers }}</div>
            <div class="label">مستخدمون محظورون</div>
        </div>
        <div class="card">
            <div class="num">{{ $pendingReports }}</div>
            <div class="label">بلاغات بانتظار المعالجة</div>
            <a href="{{ route('admin.reports.index') }}">عرض ›</a>
        </div>
        <div class="card">
            <div class="num">{{ $activeBanners }}</div>
            <div class="label">بانرات نشطة حالياً</div>
            <a href="{{ route('admin.banners.index') }}">عرض ›</a>
        </div>
    </div>
@endsection
