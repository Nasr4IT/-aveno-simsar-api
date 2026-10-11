<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لوحة التحكم') — Aveno Marketplace</title>
    <style>
        :root {
            --bg: #f5f6f8; --card: #fff; --border: #e3e5e9; --text: #1a1d23; --muted: #6b7280;
            --primary: #2563eb; --primary-dark: #1d4ed8; --danger: #dc2626; --danger-dark: #b91c1c;
            --success: #16a34a; --warning: #d97706;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: var(--bg); color: var(--text); }
        a { color: var(--primary); text-decoration: none; }
        .shell { display: flex; min-height: 100vh; }
        nav.sidebar { width: 220px; background: #111827; color: #d1d5db; padding: 20px 0; flex-shrink: 0; }
        nav.sidebar .brand { font-size: 18px; font-weight: 700; color: #fff; padding: 0 20px 20px; }
        nav.sidebar a { display: block; padding: 10px 20px; color: #d1d5db; font-size: 14px; }
        nav.sidebar a:hover, nav.sidebar a.active { background: #1f2937; color: #fff; }
        main { flex: 1; padding: 24px 32px; max-width: 1100px; }
        header.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        header.topbar form { display: inline; }
        .btn { display: inline-block; border: none; border-radius: 6px; padding: 7px 14px; font-size: 13px; cursor: pointer; font-weight: 600; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: var(--danger-dark); }
        .btn-muted { background: #e5e7eb; color: #374151; }
        .btn-muted:hover { background: #d1d5db; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
        th, td { text-align: right; padding: 10px 8px; border-bottom: 1px solid var(--border); vertical-align: top; }
        th { color: var(--muted); font-weight: 600; font-size: 12.5px; }
        .badge { display: inline-block; padding: 2px 9px; border-radius: 99px; font-size: 11.5px; font-weight: 700; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-sold { background: #e0e7ff; color: #3730a3; }
        .badge-expired { background: #f3f4f6; color: #4b5563; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
        .stat-grid .card { margin-bottom: 0; text-align: center; }
        .stat-grid .num { font-size: 28px; font-weight: 800; }
        .stat-grid .label { color: var(--muted); font-size: 13px; margin-top: 4px; }
        .flash { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 16px; margin-bottom: 16px; font-size: 13.5px; }
        .errors { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; padding: 10px 16px; margin-bottom: 16px; font-size: 13.5px; }
        .tabs { margin-bottom: 16px; }
        .tabs a { display: inline-block; padding: 6px 14px; border-radius: 99px; font-size: 13px; color: var(--muted); margin-left: 6px; }
        .tabs a.active { background: var(--primary); color: #fff; }
        input[type=text], input[type=password], input[type=number], input[type=datetime-local], textarea, select {
            width: 100%; padding: 7px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 13.5px; font-family: inherit;
        }
        label { font-size: 12.5px; color: var(--muted); display: block; margin-bottom: 3px; margin-top: 10px; }
        form.inline { display: inline; }
        .pagination { margin-top: 16px; font-size: 13px; }
        .pagination a, .pagination span { margin-left: 12px; }
        .pagination .disabled { color: #9ca3af; }
        details.reject-box summary { cursor: pointer; color: var(--danger); font-size: 13px; }
        details.reject-box .card { margin-top: 8px; padding: 10px; }
        img.thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
    </style>
</head>
<body>
<div class="shell">
    <nav class="sidebar">
        <div class="brand">Aveno — لوحة التحكم</div>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">الرئيسية</a>
        <a href="{{ route('admin.ads.index') }}" class="{{ request()->routeIs('admin.ads.*') ? 'active' : '' }}">مراجعة الإعلانات</a>
        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">المستخدمون</a>
        <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">الفئات</a>
        <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">البانرات</a>
        <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">البلاغات</a>
    </nav>
    <main>
        <header class="topbar">
            <h1 style="font-size: 20px; margin: 0;">@yield('title', 'لوحة التحكم')</h1>
            <div>
                <span style="color: var(--muted); font-size: 13px; margin-left: 10px;">{{ auth()->user()->name }}</span>
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
