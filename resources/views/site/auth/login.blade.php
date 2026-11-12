@extends('site.layout')
@section('title', 'تسجيل الدخول')
@section('hide-errors', true)
@section('content')
    <div class="card auth-box">
        <div class="logo"><img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"></div>
        <h1>تسجيل الدخول</h1>
        <p class="sub">نفس حسابك في تطبيق Semsar</p>

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('site.login.attempt') }}">
            @csrf
            <label for="phone">رقم الهاتف</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required autofocus>

            <label for="password">كلمة المرور</label>
            <input type="password" id="password" name="password" required>

            <label class="inline"><input type="checkbox" name="remember" value="1"> تذكّرني</label>

            <button class="btn btn-primary btn-block" type="submit" style="margin-top:18px">دخول</button>
        </form>

        <p class="muted small" style="text-align:center; margin:16px 0 0">ليس لديك حساب؟ <a href="{{ route('site.register') }}">أنشئ حسابًا</a></p>
    </div>
@endsection
