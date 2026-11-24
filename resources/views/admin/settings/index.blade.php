@extends('admin.layout')
@section('title', __('الإعدادات'))
@section('content')
    <form class="card" method="POST" action="{{ route('admin.settings.update') }}" style="max-width:720px">
        @csrf
        <h3 style="margin-top:0">{{ __('الدفع عبر شام كاش') }}</h3>
        <p class="muted" style="margin-top:0">{{ __('تظهر هذه البيانات للبائع عند طلب تمييز إعلانه، ليحوّل المبلغ إليها ثم يرفع صورة الإيصال.') }}</p>

        <label for="shamcash_account_name">{{ __('اسم صاحب حساب شام كاش') }}</label>
        <input type="text" id="shamcash_account_name" name="shamcash_account_name" value="{{ old('shamcash_account_name', $values['shamcash_account_name']) }}">

        <label for="shamcash_account_number">{{ __('رقم حساب / محفظة شام كاش') }}</label>
        <input type="text" id="shamcash_account_number" name="shamcash_account_number" value="{{ old('shamcash_account_number', $values['shamcash_account_number']) }}" dir="ltr">

        <label for="shamcash_instructions">{{ __('تعليمات الدفع (تظهر للبائع)') }}</label>
        <textarea id="shamcash_instructions" name="shamcash_instructions" rows="3" placeholder="{{ __('مثال: حوّل المبلغ كاملًا واكتب رقم إعلانك في ملاحظة التحويل.') }}">{{ old('shamcash_instructions', $values['shamcash_instructions']) }}</textarea>

        <h3>{{ __('بيانات التواصل') }}</h3>
        <p class="muted" style="margin-top:0">{{ __('تظهر في صفحة سياسة الخصوصية كجهة تواصل بخصوص البيانات الشخصية.') }}</p>

        <label for="contact_email">{{ __('بريد التواصل (يظهر في سياسة الخصوصية)') }}</label>
        <input type="text" id="contact_email" name="contact_email" value="{{ old('contact_email', $values['contact_email']) }}" dir="ltr">

        <label for="contact_phone">{{ __('هاتف التواصل (يظهر في سياسة الخصوصية)') }}</label>
        <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $values['contact_phone']) }}" dir="ltr">

        <button class="btn btn-primary" type="submit" style="margin-top:16px">{{ __('حفظ الإعدادات') }}</button>
    </form>
@endsection
