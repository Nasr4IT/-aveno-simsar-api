<!doctype html>
@php $isRtl = app()->getLocale() === 'ar'; @endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('تسجيل الدخول') }} — Semsar Admin Panel</title>
    <link rel="icon" href="{{ asset('images/admin/favicon.png') }}">
    <meta name="color-scheme" content="light dark">
    @include('admin.partials.theme-boot')
    <style>
        @include('admin.partials.theme-tokens')

        * { box-sizing: border-box; }
        @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
        @keyframes rise { from { opacity: 0; transform: translateY(24px) scale(.98); } to { opacity: 1; transform: none; } }
        @keyframes pop { 0% { transform: scale(.5); opacity: 0; } 70% { transform: scale(1.12); opacity: 1; } 100% { transform: scale(1); } }
        @keyframes drift { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(24px, -18px); } }
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif; margin: 0; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;
            background: linear-gradient(130deg, #021e28, #032C3A, #0c4257, #098893, #032C3A); background-size: 300% 300%;
            animation: gradientShift 18s ease infinite;
        }
        body::before, body::after { content: ""; position: absolute; border-radius: 50%; pointer-events: none; }
        body::before { width: 420px; height: 420px; top: -140px; inset-inline-end: -120px; background: radial-gradient(circle, rgba(31,242,250,.25), transparent 70%); animation: drift 12s ease-in-out infinite; }
        body::after { width: 360px; height: 360px; bottom: -140px; inset-inline-start: -100px; background: radial-gradient(circle, rgba(12,167,181,.3), transparent 70%); animation: drift 15s ease-in-out infinite reverse; }
        .tools { position: absolute; top: 18px; inset-inline-end: 18px; display: flex; gap: 8px; z-index: 2; }
        .tool { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 36px; min-width: 36px; padding: 0 10px; border-radius: 999px; border: 1px solid rgba(255,255,255,.25); background: rgba(255,255,255,.1); color: #fff; font: inherit; font-size: 12.5px; font-weight: 700; cursor: pointer; text-decoration: none; backdrop-filter: blur(6px); transition: background-color .2s, transform .15s; }
        .tool:hover { background: rgba(255,255,255,.2); }
        .tool:active { transform: scale(.94); }
        .theme-toggle .sun, :root[data-theme="dark"] .theme-toggle .moon { display: none; }
        :root[data-theme="dark"] .theme-toggle .sun { display: inline; }
        .box { position: relative; z-index: 1; background: var(--card); border-radius: 22px; padding: 36px; width: 360px; max-width: calc(100vw - 32px); box-shadow: 0 20px 60px rgba(0,0,0,.35); animation: rise .6s var(--ease) both; transition: background-color .35s; }
        .logo { display: flex; justify-content: center; margin-bottom: 14px; }
        .logo img { width: 56px; height: 56px; animation: pop .7s .15s var(--ease) both; }
        h1 { font-size: 19px; margin: 0 0 2px; text-align: center; color: var(--navy); font-weight: 800; }
        p.sub { color: var(--muted); font-size: 13px; margin: 0 0 22px; text-align: center; }
        label { font-size: 12.5px; color: var(--muted); display: block; margin-bottom: 4px; margin-top: 14px; font-weight: 600; }
        input { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; font-family: inherit; background: var(--input-bg); color: var(--text); transition: border-color .2s, box-shadow .2s; }
        input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(12,167,181,.18); }
        button.submit { width: 100%; margin-top: 22px; background: var(--teal); color: #fff; border: none; border-radius: 999px; padding: 11px; font-size: 14px; font-weight: 700; font-family: inherit; cursor: pointer; transition: background-color .2s, box-shadow .25s, transform .15s; }
        button.submit:hover { background: var(--teal-hover); box-shadow: 0 8px 20px rgba(12,167,181,.4); }
        button.submit:active { transform: scale(.97); }
        .notice { background: var(--teal-soft); color: var(--navy); border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 10px; line-height: 1.6; }
        .errors { background: var(--danger-soft); color: var(--danger-text); border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 10px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-delay: 0s !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <div class="tools">@include('admin.partials.toolbar')</div>

    <div class="box">
        <div class="logo"><img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"></div>
        <h1>Semsar Admin Panel</h1>
        <p class="sub">{{ __('تسجيل دخول المسؤولين فقط') }}</p>

        @if ($signedInMember ?? null)
            <div class="notice">
                {!! __('أنت مسجّل الدخول في الموقع باسم :name، وهذا ليس حساب مدير. سجّل الدخول بحساب المدير أدناه للمتابعة.', ['name' => '<strong>'.e($signedInMember->name).'</strong>']) !!}
            </div>
        @endif

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}">
            @csrf
            <label for="phone">{{ __('رقم الهاتف') }}</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required autofocus>

            <label for="password">{{ __('كلمة المرور') }}</label>
            <input type="password" id="password" name="password" required>

            <button class="submit" type="submit">{{ __('دخول') }}</button>
        </form>
    </div>

    @include('admin.partials.theme-script')
</body>
</html>
