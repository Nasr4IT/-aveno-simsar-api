@extends('site.layout')
@section('title', __('تعيين كلمة مرور جديدة'))
@section('hide-errors', true)
@section('content')
    <div class="card auth-box">
        <div class="logo"><img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"></div>
        <h1>{{ __('تعيين كلمة مرور جديدة') }}</h1>
        <p class="sub">{{ __('أدخل الرمز المكوّن من 6 أرقام الذي وصلك على البريد الإلكتروني.') }}</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('site.password.update') }}">
            @csrf
            <label for="identifier">{{ __('رقم الهاتف أو البريد الإلكتروني') }}</label>
            <input type="text" id="identifier" name="identifier" value="{{ old('identifier', $identifier) }}" required>

            <label for="code">{{ __('الرمز') }}</label>
            <input type="text" id="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required autofocus style="letter-spacing:6px; font-size:18px; text-align:center" dir="ltr">

            <div class="form-grid">
                <div>
                    <label for="password">{{ __('كلمة المرور الجديدة') }}</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div>
                    <label for="password_confirmation">{{ __('تأكيد كلمة المرور') }}</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
                </div>
            </div>

            <button class="btn btn-primary btn-block" type="submit" style="margin-top:18px">{{ __('حفظ كلمة المرور') }}</button>
        </form>

        <p class="muted small" style="text-align:center; margin:16px 0 0"><a href="{{ route('site.password.request') }}">{{ __('لم يصلك الرمز؟ أعد الإرسال') }}</a></p>
    </div>
@endsection
