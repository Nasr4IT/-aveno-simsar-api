<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — Semsar Admin Panel</title>
    <link rel="icon" href="{{ asset('images/admin/favicon.png') }}">
    <style>
        :root { --navy: #032C3A; --cyan: #1FF2FA; --teal: #0CA7B5; --teal-dark: #098893; }
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: radial-gradient(circle at 20% 15%, #0c4257 0%, var(--navy) 55%, #021e28 100%);
            display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0;
        }
        .box { background: #fff; border-radius: 22px; padding: 36px; width: 340px; box-shadow: 0 20px 60px rgba(0,0,0,.35); }
        .logo { display: flex; justify-content: center; margin-bottom: 14px; }
        .logo img { width: 56px; height: 56px; }
        h1 { font-size: 19px; margin: 0 0 2px; text-align: center; color: var(--navy); font-weight: 800; }
        p.sub { color: #7E97A0; font-size: 13px; margin: 0 0 22px; text-align: center; }
        label { font-size: 12.5px; color: #7E97A0; display: block; margin-bottom: 4px; margin-top: 14px; font-weight: 600; }
        input { width: 100%; padding: 10px 12px; border: 1px solid #E3ECEF; border-radius: 10px; font-size: 14px; box-sizing: border-box; }
        input:focus { outline: none; border-color: var(--teal); box-shadow: 0 0 0 3px rgba(12,167,181,.15); }
        button { width: 100%; margin-top: 22px; background: var(--teal); color: #fff; border: none; border-radius: 999px; padding: 11px; font-size: 14px; font-weight: 700; cursor: pointer; }
        button:hover { background: var(--teal-dark); }
        .errors { background: #fee2e2; color: #991b1b; border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="logo"><img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"></div>
        <h1>Semsar Admin Panel</h1>
        <p class="sub">تسجيل دخول المسؤولين فقط</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.attempt') }}">
            @csrf
            <label for="phone">رقم الهاتف</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required autofocus>

            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">دخول</button>
        </form>
    </div>
</body>
</html>
