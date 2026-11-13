@extends('site.layout')
@section('title', __('نسيت كلمة المرور؟'))
@section('hide-errors', true)
@section('content')
    <div class="card auth-box">
        <div class="logo"><img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"></div>
        <h1>{{ __('نسيت كلمة المرور؟') }}</h1>
        <p class="sub">{{ __('أدخل رقم هاتفك أو بريدك الإلكتروني، وسنرسل رمزًا إلى البريد المسجّل في حسابك.') }}</p>

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('site.password.email') }}">
            @csrf
            <label for="identifier">{{ __('رقم الهاتف أو البريد الإلكتروني') }}</label>
            <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required autofocus>
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:18px">{{ __('إرسال الرمز') }}</button>
        </form>

        <p class="muted small" style="text-align:center; margin:16px 0 0"><a href="{{ route('site.login') }}">{{ __('العودة لتسجيل الدخول') }}</a></p>
    </div>
@endsection
