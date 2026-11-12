@extends('site.layout')
@section('title', 'إنشاء حساب')
@section('hide-errors', true)
@section('content')
    <div class="card auth-box">
        <div class="logo"><img src="{{ asset('images/admin/semsar-logo.png') }}" alt="Semsar"></div>
        <h1>إنشاء حساب</h1>
        <p class="sub">حساب واحد للتطبيق والموقع</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('site.register.attempt') }}">
            @csrf
            <label for="name">الاسم</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100">

            <label for="phone">رقم الهاتف</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required>

            <label for="email">البريد الإلكتروني (اختياري)</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}">

            <div class="form-grid">
                <div>
                    <label for="password">كلمة المرور</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                <div>
                    <label for="password_confirmation">تأكيد كلمة المرور</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8">
                </div>
            </div>

            @include('site.partials.location-select', ['governorates' => $governorates])

            <button class="btn btn-primary btn-block" type="submit" style="margin-top:18px">إنشاء الحساب</button>
        </form>

        <p class="muted small" style="text-align:center; margin:16px 0 0">لديك حساب؟ <a href="{{ route('site.login') }}">سجّل الدخول</a></p>
    </div>
@endsection
