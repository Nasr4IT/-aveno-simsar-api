<!doctype html>
@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $otherLocale = $isRtl ? 'en' : 'ar';
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('الرئيسية')) — Semsar Admin Panel</title>
    <link rel="icon" href="{{ asset('images/admin/favicon.png') }}">
    <meta name="color-scheme" content="light dark">
    @include('admin.partials.theme-boot')
    <style>
        @include('admin.partials.theme-tokens')

        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: var(--bg); color: var(--text); transition: background-color .35s, color .35s; }
        a { color: var(--teal-dark); text-decoration: none; }
        .shell { display: flex; min-height: 100vh; }

        /* ---------- animations ---------- */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: none; } }
        @keyframes slideInStart { from { opacity: 0; transform: translateX(var(--slide-from, 24px)); } to { opacity: 1; transform: none; } }
        @keyframes pop { 0% { transform: scale(.6); opacity: 0; } 70% { transform: scale(1.1); opacity: 1; } 100% { transform: scale(1); } }
        @keyframes pulseRing { 0% { box-shadow: 0 0 0 0 rgba(12,167,181,.5); } 80%, 100% { box-shadow: 0 0 0 10px rgba(12,167,181,0); } }
        @keyframes spinIn { from { transform: rotate(-90deg) scale(.6); opacity: 0; } to { transform: none; opacity: 1; } }
        [dir="rtl"] { --slide-from: 24px; } [dir="ltr"] { --slide-from: -24px; }

        /* ---------- sidebar ---------- */
        nav.sidebar { width: 236px; background: var(--sidebar); color: #cfe3e9; padding: 22px 0; flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; animation: slideInStart .5s var(--ease) both; transition: background-color .35s; }
        nav.sidebar .brand { display: flex; align-items: center; gap: 10px; padding: 0 20px 24px; }
        nav.sidebar .brand img { width: 34px; height: 34px; transition: transform .5s var(--ease); }
        nav.sidebar .brand:hover img { transform: rotate(-12deg) scale(1.08); }
        nav.sidebar .brand .brand-text strong { display: block; color: #fff; font-size: 16px; letter-spacing: .2px; }
        nav.sidebar .brand .brand-text span { display: block; color: var(--cyan); font-size: 11.5px; margin-top: 1px; }
        nav.sidebar .links { padding: 0 12px; }
        nav.sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #a9c2c9; font-size: 13.5px; border-radius: 10px; margin-bottom: 2px; position: relative; transition: background-color .2s, color .2s, padding .2s; animation: slideInStart .45s var(--ease) both; }
        nav.sidebar .links a:nth-child(1) { animation-delay: .05s; } nav.sidebar .links a:nth-child(2) { animation-delay: .1s; }
        nav.sidebar .links a:nth-child(3) { animation-delay: .15s; } nav.sidebar .links a:nth-child(4) { animation-delay: .2s; }
        nav.sidebar .links a:nth-child(5) { animation-delay: .25s; } nav.sidebar .links a:nth-child(6) { animation-delay: .3s; }
        nav.sidebar a .nav-icon { flex-shrink: 0; opacity: .85; transition: transform .25s var(--ease); }
        nav.sidebar a:hover { background: var(--navy-light); color: #fff; padding-inline-start: 16px; }
        nav.sidebar a:hover .nav-icon { transform: scale(1.12); }
        nav.sidebar a.active { background: var(--teal); color: #fff; box-shadow: 0 6px 16px rgba(12,167,181,.35); }
        nav.sidebar a.active .nav-icon { opacity: 1; }
        nav.sidebar .count { margin-inline-start: auto; background: var(--cyan); color: var(--brand-navy); font-size: 11px; font-weight: 800; padding: 1px 8px; border-radius: 999px; animation: pulseRing 2.4s infinite; }

        /* ---------- main + top bar ---------- */
        main { flex: 1; padding: 26px 34px; max-width: 1180px; min-width: 0; animation: fadeIn .4s ease both; }
        header.topbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 22px; flex-wrap: wrap; }
        header.topbar h1 { font-size: 21px; margin: 0; font-weight: 800; color: var(--navy); animation: fadeUp .5s var(--ease) both; }
        header.topbar .end { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        header.topbar form { display: inline; margin: 0; }
        header.topbar .who { color: var(--muted); font-size: 13px; }
        .tool { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; min-width: 36px; padding: 0 10px; border-radius: 999px; border: 1px solid var(--border); background: var(--card); color: var(--navy); font: inherit; font-size: 12.5px; font-weight: 700; cursor: pointer; transition: background-color .2s, border-color .2s, transform .15s; }
        .tool:hover { border-color: var(--teal); background: var(--teal-soft); }
        .tool:active { transform: scale(.94); }
        .theme-toggle .sun, :root[data-theme="dark"] .theme-toggle .moon { display: none; }
        :root[data-theme="dark"] .theme-toggle .sun { display: inline; }
        .theme-toggle svg { animation: spinIn .45s var(--ease); }

        /* ---------- buttons ---------- */
        .btn { display: inline-block; border: none; border-radius: 999px; padding: 8px 18px; font-size: 13px; cursor: pointer; font-weight: 700; font-family: inherit; transition: background-color .2s, box-shadow .25s, transform .15s; }
        .btn:active { transform: scale(.96); }
        .btn-primary { background: var(--teal); color: #fff; }
        .btn-primary:hover { background: var(--teal-hover); box-shadow: 0 6px 16px rgba(12,167,181,.35); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: var(--danger-dark); box-shadow: 0 6px 16px rgba(220,38,38,.3); }
        .btn-muted { background: var(--soft-bg); color: var(--navy); }
        .btn-muted:hover { background: var(--soft-bg-hover); }

        /* ---------- cards & tables ---------- */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; box-shadow: var(--shadow); animation: fadeUp .5s var(--ease) both; transition: background-color .35s, border-color .35s, box-shadow .25s; }
        main > .card:nth-of-type(2) { animation-delay: .08s; } main > .card:nth-of-type(3) { animation-delay: .16s; }
        h3 { color: var(--navy); }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: start; padding: 11px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; }
        tbody tr { animation: fadeUp .4s var(--ease) both; transition: background-color .2s; }
        tbody tr:hover { background: var(--bg); }
        tbody tr:nth-child(1) { animation-delay: .04s; } tbody tr:nth-child(2) { animation-delay: .08s; } tbody tr:nth-child(3) { animation-delay: .12s; }
        tbody tr:nth-child(4) { animation-delay: .16s; } tbody tr:nth-child(5) { animation-delay: .2s; } tbody tr:nth-child(6) { animation-delay: .24s; }
        tbody tr:nth-child(7) { animation-delay: .28s; } tbody tr:nth-child(8) { animation-delay: .32s; } tbody tr:nth-child(n+9) { animation-delay: .36s; }

        .badge { display: inline-block; padding: 3px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
        .badge-pending { background: var(--warn-soft); color: var(--warn-text); }
        .badge-approved { background: var(--success-soft); color: var(--success-text); }
        .badge-rejected { background: var(--danger-soft); color: var(--danger-text); }
        .badge-sold { background: var(--indigo-soft); color: var(--indigo-text); }
        .badge-expired { background: var(--gray-soft); color: var(--gray-text); }

        /* ---------- dashboard stats ---------- */
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; }
        .stat-grid .card { margin-bottom: 0; transition: transform .3s var(--ease), box-shadow .3s, border-color .3s; }
        .stat-grid .card:nth-child(1) { animation-delay: .05s; } .stat-grid .card:nth-child(2) { animation-delay: .12s; }
        .stat-grid .card:nth-child(3) { animation-delay: .19s; } .stat-grid .card:nth-child(4) { animation-delay: .26s; }
        .stat-grid .card:nth-child(5) { animation-delay: .33s; }
        .stat-grid .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: var(--teal); }
        .stat-grid .icon-badge { width: 40px; height: 40px; border-radius: 12px; background: var(--teal-soft); color: var(--teal-dark); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; transition: background-color .25s, color .25s, transform .35s var(--ease); }
        .stat-grid .card:hover .icon-badge { background: var(--teal); color: #fff; transform: rotate(-8deg) scale(1.08); }
        .stat-grid .num { font-size: 28px; font-weight: 800; color: var(--navy); font-variant-numeric: tabular-nums; }
        .stat-grid .label { color: var(--muted); font-size: 13px; margin-top: 3px; }
        .stat-grid a { font-size: 12.5px; font-weight: 700; display: inline-block; margin-top: 8px; }

        .flash { background: var(--success-soft); color: var(--success-text); border: 1px solid var(--success-border); border-radius: var(--radius-sm); padding: 11px 16px; margin-bottom: 16px; font-size: 13.5px; animation: slideDown .45s var(--ease) both; }
        .errors { background: var(--danger-soft); color: var(--danger-text); border: 1px solid var(--danger-border); border-radius: var(--radius-sm); padding: 11px 16px; margin-bottom: 16px; font-size: 13.5px; animation: slideDown .45s var(--ease) both; }

        .tabs { margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 6px; }
        .tabs a { display: inline-block; padding: 7px 16px; border-radius: 999px; font-size: 13px; color: var(--muted); background: var(--card); border: 1px solid var(--border); transition: background-color .2s, color .2s, border-color .2s, transform .15s; }
        .tabs a:hover { border-color: var(--teal); color: var(--teal-dark); transform: translateY(-1px); }
        .tabs a.active { background: var(--teal); color: #fff; border-color: var(--teal); }

        input[type=text], input[type=password], input[type=number], input[type=datetime-local], input[type=file], textarea, select {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 13.5px; font-family: inherit; background: var(--input-bg); color: var(--text); transition: border-color .2s, box-shadow .2s, background-color .35s;
        }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(12,167,181,.18); }
        input[type=checkbox] { accent-color: var(--teal); }
        label { font-size: 12.5px; color: var(--muted); display: block; margin-bottom: 3px; margin-top: 10px; font-weight: 600; }
        form.inline { display: inline; }
        details > summary { transition: color .2s; }
        details[open] > *:not(summary) { animation: fadeUp .3s var(--ease) both; }

        .pagination { display: flex; justify-content: center; gap: 6px; margin: 16px 0; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 7px 13px; border-radius: 999px; border: 1px solid var(--border); background: var(--card); font-size: 13px; color: var(--navy); transition: border-color .2s, transform .15s; }
        .pagination a:hover { border-color: var(--teal); transform: translateY(-1px); }
        .pagination span.current { background: var(--teal); color: #fff; border-color: var(--teal); }
        .pagination span.disabled { color: var(--muted); }
        details.reject-box summary { cursor: pointer; color: var(--danger); font-size: 13px; }
        details.reject-box .card { margin-top: 8px; padding: 10px; }
        img.thumb { width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-sm); vertical-align: middle; margin-inline-end: 6px; transition: transform .25s var(--ease); }
        img.thumb:hover { transform: scale(1.6); position: relative; z-index: 2; box-shadow: var(--shadow-lg); }
        .muted { color: var(--muted); }

        /* ---------- phones: the sidebar becomes a scrolling top bar ---------- */
        @media (max-width: 860px) {
            .shell { flex-direction: column; }
            nav.sidebar { width: auto; height: auto; position: sticky; top: 0; z-index: 10; padding: 10px 0; display: flex; align-items: center; }
            nav.sidebar .brand { padding: 0 12px; }
            nav.sidebar .brand .brand-text span { display: none; }
            nav.sidebar .links { display: flex; overflow-x: auto; padding: 0 8px; gap: 4px; }
            nav.sidebar a { white-space: nowrap; margin: 0; }
            nav.sidebar a:hover { padding-inline-start: 12px; }
            main { padding: 18px 16px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-delay: 0s !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
@php
    $pendingCount = \App\Models\Ad::where('status', 'pending')->count();
    $reportCount = \App\Models\Report::where('status', 'pending')->count();
@endphp
<div class="shell">
    <nav class="sidebar">
        <a class="brand" href="{{ route('admin.dashboard') }}" style="padding-top:0;padding-bottom:0;animation:none;background:none">
            <img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar">
            <div class="brand-text">
                <strong>Semsar</strong>
                <span>{{ __('لوحة التحكم') }}</span>
            </div>
        </a>
        <div class="links">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'home']) {{ __('الرئيسية') }}
            </a>
            <a href="{{ route('admin.ads.index') }}" class="{{ request()->routeIs('admin.ads.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'ads']) {{ __('مراجعة الإعلانات') }}
                @if ($pendingCount) <span class="count">{{ $pendingCount }}</span> @endif
            </a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'users']) {{ __('المستخدمون') }}
            </a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'grid']) {{ __('الفئات') }}
            </a>
            <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'image']) {{ __('البانرات') }}
            </a>
            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'flag']) {{ __('البلاغات') }}
                @if ($reportCount) <span class="count">{{ $reportCount }}</span> @endif
            </a>
        </div>
    </nav>
    <main>
        <header class="topbar">
            <h1>@yield('title', __('الرئيسية'))</h1>
            <div class="end">
                @include('admin.partials.toolbar')
                <span class="who">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn-muted" type="submit">{{ __('تسجيل الخروج') }}</button>
                </form>
            </div>
        </header>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</div>
@include('admin.partials.theme-script')
@stack('scripts')
</body>
</html>
