@extends('site.layout')
@section('title', __('تسجيل الدخول'))
@section('hide-errors', true)
@section('content')
    <div class="card auth-box">
        <div class="logo"><img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"></div>
        <h1>{{ __('تسجيل الدخول') }}</h1>
        <p class="sub">{{ __('نفس حسابك في تطبيق Semsar') }}</p>

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('site.login.attempt') }}">
            @csrf
            <label for="phone">{{ __('رقم الهاتف') }}</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required autofocus>

            <label for="password">{{ __('كلمة المرور') }}</label>
            <input type="password" id="password" name="password" required>

            <label class="inline"><input type="checkbox" name="remember" value="1"> {{ __('تذكّرني') }}</label>

            <button class="btn btn-primary btn-block" type="submit" style="margin-top:18px">{{ __('دخول') }}</button>
        </form>

        <p class="muted small" style="text-align:center; margin:16px 0 0">{{ __('ليس لديك حساب؟') }} <a href="{{ route('site.register') }}">{{ __('أنشئ حسابًا') }}</a></p>
    </div>
@endsection
