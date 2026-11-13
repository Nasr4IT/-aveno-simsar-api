<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'الرئيسية') — Semsar</title>
    <link rel="icon" href="{{ asset('images/admin/favicon.png') }}">
    <style>
        :root {
            --navy: #032C3A; --navy-light: #0c4257; --cyan: #1FF2FA; --teal: #0CA7B5; --teal-dark: #098893; --teal-soft: #E3FAFB;
            --bg: #F3F8FA; --card: #fff; --border: #E3ECEF; --text: #032C3A; --muted: #7E97A0;
            --danger: #dc2626; --danger-soft: #fee2e2; --success: #16a34a; --success-soft: #dcfce7; --warning: #d97706;
            --radius: 16px; --radius-sm: 10px; --shadow: 0 2px 14px rgba(3,44,58,.07);
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: var(--bg); color: var(--text); line-height: 1.55; }
        a { color: var(--teal-dark); text-decoration: none; }
        img { max-width: 100%; display: block; }
        h1, h2, h3 { color: var(--navy); margin: 0 0 8px; line-height: 1.3; }
        h1 { font-size: 24px; font-weight: 800; } h2 { font-size: 18px; font-weight: 800; } h3 { font-size: 15px; font-weight: 700; }
        .container { max-width: 1180px; margin: 0 auto; padding: 0 16px; }
        .ico { flex-shrink: 0; vertical-align: middle; }

        /* ---- top bar ---- */
        .topbar { background: var(--card); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 20; }
        .topbar .container { display: flex; align-items: center; gap: 16px; height: 64px; }
        .brand { display: flex; align-items: center; gap: 8px; color: var(--navy); font-weight: 800; font-size: 19px; }
        .brand img { width: 34px; height: 34px; }
        .topbar form.search { flex: 1; max-width: 520px; position: relative; }
        .topbar form.search input { width: 100%; padding: 10px 40px 10px 14px; border: 1px solid var(--border); border-radius: 999px; font-size: 14px; font-family: inherit; background: var(--bg); }
        .topbar form.search .ico { position: absolute; inset-inline-start: 14px; top: 50%; transform: translateY(-50%); color: var(--muted); }
        .topbar nav { display: flex; align-items: center; gap: 4px; margin-inline-start: auto; }
        .topbar nav a { display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 999px; font-size: 14px; color: var(--navy); font-weight: 600; }
        .topbar nav a:hover { background: var(--bg); }
        .topbar nav a.active { color: var(--teal-dark); background: var(--teal-soft); }
        .topbar nav .badge-count { background: var(--teal); color: #fff; border-radius: 999px; font-size: 11px; padding: 1px 7px; font-weight: 700; }
        .topbar form.inline { display: inline; }

        /* ---- buttons ---- */
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid transparent; border-radius: 999px; padding: 10px 20px; font-size: 14px; cursor: pointer; font-weight: 700; font-family: inherit; line-height: 1.2; }
        .btn-primary { background: var(--teal); color: #fff; }
        .btn-primary:hover { background: var(--teal-dark); color: #fff; }
        .btn-outline { background: var(--card); color: var(--teal-dark); border-color: var(--teal); }
        .btn-outline:hover { background: var(--teal-soft); }
        .btn-muted { background: #E7EFF1; color: var(--navy); }
        .btn-muted:hover { background: #d9e5e8; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-sm { padding: 7px 14px; font-size: 13px; }
        .btn-block { width: 100%; }
        .btn[disabled] { opacity: .6; cursor: not-allowed; }

        /* ---- cards & layout ---- */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
        .section { margin: 28px 0; }
        .section-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
        .section-head a { font-size: 13.5px; font-weight: 700; }
        .muted { color: var(--muted); }
        .small { font-size: 12.5px; }
        .row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .empty { text-align: center; color: var(--muted); padding: 40px 16px; }
        .empty .ico { color: var(--border); margin: 0 auto 10px; display: block; }

        /* ---- hero ---- */
        .hero { padding: 26px 0 6px; }
        .hero h1 { font-size: 26px; }
        .hero form { position: relative; margin-top: 14px; }
        .hero form input { width: 100%; padding: 15px 48px 15px 16px; border: 1px solid var(--border); border-radius: 999px; font-size: 15px; font-family: inherit; background: var(--card); box-shadow: var(--shadow); }
        .hero form .ico { position: absolute; inset-inline-start: 18px; top: 50%; transform: translateY(-50%); color: var(--muted); }

        /* ---- banners ---- */
        .banners { display: flex; gap: 12px; overflow-x: auto; scroll-snap-type: x mandatory; padding-bottom: 6px; }
        .banners a, .banners div { flex: 0 0 85%; max-width: 560px; scroll-snap-align: start; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
        .banners img { width: 100%; aspect-ratio: 21/9; object-fit: cover; }

        /* ---- category tiles ---- */
        .cat-tiles { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
        .cat-tile { display: flex; align-items: center; gap: 12px; background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 14px; box-shadow: var(--shadow); color: var(--navy); }
        .cat-tile:hover { border-color: var(--teal); }
        .cat-tile .icon { width: 42px; height: 42px; border-radius: 12px; background: var(--teal-soft); color: var(--teal-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .cat-tile strong { display: block; font-size: 14.5px; }
        .cat-tile span { color: var(--muted); font-size: 12.5px; }

        /* ---- ad cards ---- */
        .ad-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 16px; }
        .rail { display: flex; gap: 14px; overflow-x: auto; padding-bottom: 8px; scroll-snap-type: x proximity; }
        .rail .ad-card { flex: 0 0 230px; scroll-snap-align: start; }
        .ad-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); display: flex; flex-direction: column; color: var(--text); position: relative; transition: transform .12s; }
        .ad-card:hover { transform: translateY(-2px); }
        .ad-card .img { aspect-ratio: 4/3; background: var(--bg); overflow: hidden; position: relative; }
        .ad-card .img img { width: 100%; height: 100%; object-fit: cover; }
        .ad-card .img .noimg { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--border); }
        .ad-card .pill { position: absolute; top: 10px; inset-inline-start: 10px; background: var(--teal); color: #fff; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; }
        .ad-card .pill.sold { background: #3730a3; }
        .ad-card .body { padding: 10px 12px 12px; display: flex; flex-direction: column; gap: 3px; }
        .ad-card .title { font-weight: 700; font-size: 14px; color: var(--navy); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.6em; }
        .ad-card .price { color: var(--teal-dark); font-weight: 800; font-size: 15px; }
        .ad-card .meta { display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 12px; gap: 6px; }
        .ad-card .meta span { display: inline-flex; align-items: center; gap: 4px; min-width: 0; }
        .ad-card .fav { position: absolute; top: 8px; inset-inline-end: 8px; width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,.92); border: none; color: var(--navy); display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .ad-card .fav.on { color: var(--teal-dark); }

        /* ---- badges ---- */
        .badge { display: inline-block; padding: 3px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: var(--success-soft); color: #166534; }
        .badge-rejected { background: var(--danger-soft); color: #991b1b; }
        .badge-sold { background: #e0e7ff; color: #3730a3; }
        .badge-expired { background: #f3f4f6; color: #4b5563; }
        .chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 999px; font-size: 13px; color: var(--navy); background: var(--card); border: 1px solid var(--border); white-space: nowrap; }
        .chip.active { background: var(--teal); color: #fff; border-color: var(--teal); }
        .chips { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 6px; }

        /* ---- forms ---- */
        label { font-size: 12.5px; color: var(--muted); display: block; margin-bottom: 4px; margin-top: 12px; font-weight: 600; }
        label.inline { display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; }
        input[type=text], input[type=password], input[type=number], input[type=email], input[type=tel], textarea, select {
            width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; font-family: inherit; background: #fff; color: var(--text);
        }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(12,167,181,.15); }
        input[type=checkbox], input[type=radio] { width: auto; accent-color: var(--teal); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px; }
        .field-error { color: var(--danger); font-size: 12.5px; margin-top: 4px; }
        .auth-box { max-width: 420px; margin: 36px auto; }
        .auth-box .logo { display: flex; justify-content: center; margin-bottom: 10px; }
        .auth-box .logo img { width: 60px; height: 60px; }
        .auth-box h1 { text-align: center; }
        .auth-box p.sub { text-align: center; color: var(--muted); margin: 0 0 14px; font-size: 13.5px; }

        /* ---- flash ---- */
        .flash { background: var(--success-soft); color: #166534; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 11px 16px; margin: 16px 0; font-size: 13.5px; }
        .errors { background: var(--danger-soft); color: #991b1b; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 11px 16px; margin: 16px 0; font-size: 13.5px; }

        /* ---- explore ---- */
        .explore { display: grid; grid-template-columns: 280px 1fr; gap: 20px; align-items: start; }
        .filters summary { cursor: pointer; font-weight: 700; color: var(--navy); list-style: none; display: flex; align-items: center; gap: 8px; }
        .filters summary::-webkit-details-marker { display: none; }
        .results-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .results-head select { width: auto; padding: 8px 12px; border-radius: 999px; }

        /* ---- detail ---- */
        .detail { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
        .gallery .main { aspect-ratio: 4/3; border-radius: var(--radius); overflow: hidden; background: var(--bg); box-shadow: var(--shadow); }
        .gallery .main img { width: 100%; height: 100%; object-fit: cover; }
        .gallery .thumbs { display: flex; gap: 8px; margin-top: 10px; overflow-x: auto; }
        .gallery .thumbs img { width: 76px; height: 60px; object-fit: cover; border-radius: 10px; border: 2px solid transparent; cursor: pointer; }
        .gallery .thumbs img.active { border-color: var(--teal); }
        .price-big { color: var(--teal-dark); font-size: 26px; font-weight: 800; }
        .specs { display: flex; flex-wrap: wrap; gap: 8px; }
        .specs span { background: var(--bg); border: 1px solid var(--border); border-radius: 999px; padding: 6px 12px; font-size: 13px; }
        .kv { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 12px; font-size: 14px; }
        .kv dt { color: var(--muted); } .kv dd { margin: 0; font-weight: 600; }
        .seller { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 48px; height: 48px; border-radius: 50%; background: var(--teal-soft); color: var(--teal-dark); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 18px; flex-shrink: 0; }
        .stars { color: #f59e0b; display: inline-flex; gap: 1px; }
        .stars .ico.off { color: var(--border); }
        .actions { display: grid; gap: 10px; margin-top: 14px; }

        /* ---- messages & notifications ---- */
        .convo { display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--border); color: var(--text); }
        .convo:last-child { border-bottom: none; }
        .convo img { width: 56px; height: 44px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
        .convo .who { flex: 1; min-width: 0; }
        .convo .who strong { display: block; }
        .convo .unread { background: var(--teal); color: #fff; border-radius: 999px; font-size: 11px; padding: 2px 8px; font-weight: 700; }
        .thread { display: flex; flex-direction: column; gap: 8px; max-height: 60vh; overflow-y: auto; padding: 4px; }
        .bubble { max-width: 78%; padding: 10px 14px; border-radius: 16px; background: var(--bg); border: 1px solid var(--border); align-self: flex-start; font-size: 14px; }
        .bubble.mine { background: var(--teal); color: #fff; border-color: var(--teal); align-self: flex-end; }
        .bubble .t { display: block; font-size: 11px; opacity: .7; margin-top: 4px; }
        .compose { display: flex; gap: 8px; margin-top: 12px; }
        .compose input { flex: 1; border-radius: 999px; }
        .notif { display: flex; gap: 12px; align-items: flex-start; padding: 12px 0; border-bottom: 1px solid var(--border); }
        .notif:last-child { border-bottom: none; }
        .notif.unread { background: var(--teal-soft); margin: 0 -20px; padding: 12px 20px; }
        .notif .dot { width: 36px; height: 36px; border-radius: 50%; background: var(--card); border: 1px solid var(--border); color: var(--teal-dark); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .notif form { margin: 0; }
        .notif button { background: none; border: none; padding: 0; font: inherit; color: var(--text); cursor: pointer; text-align: right; }
        .rating-row { padding: 10px 0; border-bottom: 1px solid var(--border); }
        .rating-row:last-child { border-bottom: none; }

        /* ---- tables, pagination ---- */
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: right; padding: 11px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 700; font-size: 12px; }
        .pagination { display: flex; justify-content: center; gap: 6px; margin: 24px 0; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 14px; border-radius: 999px; border: 1px solid var(--border); background: var(--card); font-size: 13.5px; color: var(--navy); }
        .pagination span.current { background: var(--teal); color: #fff; border-color: var(--teal); }
        .pagination span.disabled { color: var(--muted); }

        /* ---- bottom nav (mobile) ---- */
        .bottom-nav { display: none; }
        footer { color: var(--muted); font-size: 12.5px; text-align: center; padding: 30px 16px 20px; }

        @media (max-width: 860px) {
            .explore, .detail { grid-template-columns: 1fr; }
            .topbar nav .label { display: none; }
            .topbar nav a.hide-mobile { display: none; }
        }
        @media (max-width: 640px) {
            body { padding-bottom: 72px; }
            .topbar form.search { display: none; }
            .topbar nav { display: none; }
            .form-grid { grid-template-columns: 1fr; }
            .bottom-nav { display: flex; position: fixed; bottom: 0; inset-inline: 0; background: var(--card); border-top: 1px solid var(--border); z-index: 30; justify-content: space-around; padding: 6px 4px 8px; }
            .bottom-nav a { display: flex; flex-direction: column; align-items: center; gap: 2px; font-size: 11px; color: var(--muted); font-weight: 600; min-width: 56px; }
            .bottom-nav a.active { color: var(--teal-dark); }
            .bottom-nav a.sell .ico { width: 44px; height: 44px; border-radius: 50%; background: var(--teal); color: #fff; padding: 10px; margin-top: -18px; box-shadow: 0 4px 12px rgba(12,167,181,.4); }
        }
    </style>
    @stack('head')
</head>
<body>
@php
    $navUser = auth()->user();
    $unread = $navUser ? $navUser->unreadNotifications()->count() : 0;
@endphp
<header class="topbar">
    <div class="container">
        <a class="brand" href="{{ route('site.home') }}">
            <img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"> Semsar
        </a>
        <form class="search" method="GET" action="{{ route('site.explore') }}">
            @include('site.partials.icon', ['name' => 'search', 'size' => 18])
            <input type="text" name="q" value="{{ request('q') }}" placeholder="عمّ تبحث؟" aria-label="بحث">
        </form>
        <nav>
            <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'home', 'size' => 18]) <span class="label">الرئيسية</span></a>
            <a href="{{ route('site.explore') }}" class="{{ request()->routeIs('site.explore') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'search', 'size' => 18]) <span class="label">استكشف</span></a>
            @if (Route::has('site.sell'))
                <a href="{{ route('site.sell') }}" class="{{ request()->routeIs('site.sell*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'plus', 'size' => 18]) <span class="label">بيع</span></a>
            @endif
            @if ($navUser)
                @if (Route::has('site.messages'))
                    <a href="{{ route('site.messages') }}" class="{{ request()->routeIs('site.messages*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'chat', 'size' => 18]) <span class="label">الرسائل</span></a>
                @endif
                @if (Route::has('site.notifications'))
                    <a href="{{ route('site.notifications') }}" class="{{ request()->routeIs('site.notifications*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'bell', 'size' => 18]) @if ($unread) <span class="badge-count">{{ $unread }}</span> @endif</a>
                @endif
                <a href="{{ Route::has('site.my.ads') ? route('site.my.ads') : route('site.home') }}" class="{{ request()->routeIs('site.my.*') || request()->routeIs('site.profile*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'user', 'size' => 18]) <span class="label">{{ $navUser->name }}</span></a>
                <form class="inline" method="POST" action="{{ route('site.logout') }}">
                    @csrf
                    <button class="btn btn-muted btn-sm" type="submit">خروج</button>
                </form>
            @else
                <a href="{{ route('site.login') }}" class="btn btn-primary btn-sm">تسجيل الدخول</a>
            @endif
        </nav>
    </div>
</header>

<main class="container">
    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif
    @if ($errors->any() && ! View::hasSection('hide-errors'))
        <div class="errors">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</main>

<footer>Semsar — سوق الإعلانات المبوّبة</footer>

<nav class="bottom-nav">
    <a href="{{ route('site.home') }}" class="{{ request()->routeIs('site.home') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'home', 'size' => 22]) الرئيسية</a>
    <a href="{{ route('site.explore') }}" class="{{ request()->routeIs('site.explore') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'search', 'size' => 22]) استكشف</a>
    <a href="{{ Route::has('site.sell') ? route('site.sell') : route('site.login') }}" class="sell">@include('site.partials.icon', ['name' => 'plus', 'size' => 22]) بيع</a>
    <a href="{{ Route::has('site.messages') ? route('site.messages') : route('site.login') }}" class="{{ request()->routeIs('site.messages*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'chat', 'size' => 22]) الرسائل</a>
    <a href="{{ $navUser ? (Route::has('site.my.ads') ? route('site.my.ads') : route('site.home')) : route('site.login') }}" class="{{ request()->routeIs('site.my.*') || request()->routeIs('site.profile*') ? 'active' : '' }}">@include('site.partials.icon', ['name' => 'user', 'size' => 22]) حسابي</a>
</nav>

@stack('scripts')
</body>
</html>
