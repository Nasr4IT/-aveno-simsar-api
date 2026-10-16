<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'الرئيسية') — Semsar Admin Panel</title>
    <link rel="icon" href="{{ asset('images/admin/favicon.png') }}">
    <style>
        :root {
            --navy: #032C3A; --navy-light: #0c4257; --cyan: #1FF2FA; --teal: #0CA7B5; --teal-dark: #098893;
            --bg: #F3F8FA; --card: #fff; --border: #E3ECEF; --text: #032C3A; --muted: #7E97A0;
            --danger: #dc2626; --danger-dark: #b91c1c; --success: #16a34a; --warning: #d97706;
            --radius: 16px; --radius-sm: 10px; --shadow: 0 2px 14px rgba(3,44,58,.07);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: var(--bg); color: var(--text); }
        a { color: var(--teal-dark); text-decoration: none; }
        .shell { display: flex; min-height: 100vh; }

        nav.sidebar { width: 236px; background: var(--navy); color: #cfe3e9; padding: 22px 0; flex-shrink: 0; }
        nav.sidebar .brand { display: flex; align-items: center; gap: 10px; padding: 0 20px 24px; }
        nav.sidebar .brand img { width: 34px; height: 34px; }
        nav.sidebar .brand .brand-text strong { display: block; color: #fff; font-size: 16px; letter-spacing: .2px; }
        nav.sidebar .brand .brand-text span { display: block; color: var(--cyan); font-size: 11.5px; margin-top: 1px; }
        nav.sidebar .links { padding: 0 12px; }
        nav.sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; color: #a9c2c9; font-size: 13.5px; border-radius: 10px; margin-bottom: 2px; }
        nav.sidebar a .nav-icon { flex-shrink: 0; opacity: .85; }
        nav.sidebar a:hover { background: var(--navy-light); color: #fff; }
        nav.sidebar a.active { background: var(--teal); color: #fff; }
        nav.sidebar a.active .nav-icon { opacity: 1; }

        main { flex: 1; padding: 26px 34px; max-width: 1180px; }
        header.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        header.topbar h1 { font-size: 21px; margin: 0; font-weight: 800; color: var(--navy); }
        header.topbar form { display: inline; }
        header.topbar .who { color: var(--muted); font-size: 13px; margin-left: 12px; }

        .btn { display: inline-block; border: none; border-radius: 999px; padding: 8px 18px; font-size: 13px; cursor: pointer; font-weight: 700; }
        .btn-primary { background: var(--teal); color: #fff; }
        .btn-primary:hover { background: var(--teal-dark); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: var(--danger-dark); }
        .btn-muted { background: #E7EFF1; color: var(--navy); }
        .btn-muted:hover { background: #d9e5e8; }

        .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; box-shadow: var(--shadow); }

        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: right; padding: 11px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: .3px; }

        .badge { display: inline-block; padding: 3px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-sold { background: #e0e7ff; color: #3730a3; }
        .badge-expired { background: #f3f4f6; color: #4b5563; }

        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 16px; }
        .stat-grid .card { margin-bottom: 0; }
        .stat-grid .icon-badge { width: 40px; height: 40px; border-radius: 12px; background: #E3FAFB; color: var(--teal-dark); display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
        .stat-grid .num { font-size: 28px; font-weight: 800; color: var(--navy); }
        .stat-grid .label { color: var(--muted); font-size: 13px; margin-top: 3px; }
        .stat-grid a { font-size: 12.5px; font-weight: 700; display: inline-block; margin-top: 8px; }

        .flash { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; border-radius: var(--radius-sm); padding: 11px 16px; margin-bottom: 16px; font-size: 13.5px; }
        .errors { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 11px 16px; margin-bottom: 16px; font-size: 13.5px; }

        .tabs { margin-bottom: 16px; }
        .tabs a { display: inline-block; padding: 7px 16px; border-radius: 999px; font-size: 13px; color: var(--muted); margin-left: 6px; background: var(--card); border: 1px solid var(--border); }
        .tabs a.active { background: var(--teal); color: #fff; border-color: var(--teal); }

        input[type=text], input[type=password], input[type=number], input[type=datetime-local], textarea, select {
            width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 13.5px; font-family: inherit; background: #fff; color: var(--text);
        }
        input:focus, textarea:focus, select:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(12,167,181,.15); }
        label { font-size: 12.5px; color: var(--muted); display: block; margin-bottom: 3px; margin-top: 10px; font-weight: 600; }
        form.inline { display: inline; }

        .pagination { margin-top: 16px; font-size: 13px; }
        .pagination a, .pagination span { margin-left: 8px; }
        details.reject-box summary { cursor: pointer; color: var(--danger); font-size: 13px; }
        details.reject-box .card { margin-top: 8px; padding: 10px; }
        img.thumb { width: 48px; height: 48px; object-fit: cover; border-radius: var(--radius-sm); }
    </style>
</head>
<body>
<div class="shell">
    <nav class="sidebar">
        <div class="brand">
            <img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar">
            <div class="brand-text">
                <strong>Semsar</strong>
                <span>لوحة التحكم</span>
            </div>
        </div>
        <div class="links">
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'home']) الرئيسية
            </a>
            <a href="{{ route('admin.ads.index') }}" class="{{ request()->routeIs('admin.ads.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'ads']) مراجعة الإعلانات
            </a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'users']) المستخدمون
            </a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'grid']) الفئات
            </a>
            <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'image']) البانرات
            </a>
            <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                @include('admin.partials.icon', ['name' => 'flag']) البلاغات
            </a>
        </div>
    </nav>
    <main>
        <header class="topbar">
            <h1>@yield('title', 'الرئيسية')</h1>
            <div>
                <span class="who">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn-muted" type="submit">تسجيل الخروج</button>
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
</body>
</html>
