<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — لوحة التحكم</title>
    <style>
        body { font-family: "Segoe UI", Tahoma, Arial, sans-serif; background: #111827; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .box { background: #fff; border-radius: 12px; padding: 32px; width: 320px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        p.sub { color: #6b7280; font-size: 13px; margin: 0 0 20px; }
        label { font-size: 12.5px; color: #6b7280; display: block; margin-bottom: 4px; margin-top: 14px; }
        input { width: 100%; padding: 9px 10px; border: 1px solid #e3e5e9; border-radius: 6px; font-size: 14px; box-sizing: border-box; }
        button { width: 100%; margin-top: 20px; background: #2563eb; color: #fff; border: none; border-radius: 6px; padding: 10px; font-size: 14px; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .errors { background: #fee2e2; color: #991b1b; border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Aveno Marketplace</h1>
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
