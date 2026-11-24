@extends('site.layout')
@section('title', __('سياسة الخصوصية'))
@section('content')
    @php $contactEmail = \App\Models\Setting::get('contact_email'); $contactPhone = \App\Models\Setting::get('contact_phone'); @endphp
    <article class="card legal" style="margin:20px auto; max-width:820px">
        <h1>{{ __('سياسة الخصوصية') }}</h1>
        <p class="muted small" style="margin-top:0">{{ __('آخر تحديث: :date', ['date' => \Illuminate\Support\Carbon::parse(\App\Http\Controllers\Site\PageController::PRIVACY_UPDATED)->translatedFormat('j F Y')]) }}</p>

        @include(app()->getLocale() === 'en' ? 'site.legal.privacy-en' : 'site.legal.privacy-ar')

        <h2>{{ __('تواصل معنا') }}</h2>
        @if ($contactEmail || $contactPhone)
            <p>{{ __('لأي سؤال أو طلب بخصوص بياناتك الشخصية:') }}</p>
            <ul>
                @if ($contactEmail)<li>{{ __('البريد الإلكتروني:') }} <a href="mailto:{{ $contactEmail }}" dir="ltr">{{ $contactEmail }}</a></li>@endif
                @if ($contactPhone)<li>{{ __('الهاتف:') }} <a href="tel:{{ preg_replace('/[^\d+]/', '', $contactPhone) }}" dir="ltr">{{ $contactPhone }}</a></li>@endif
            </ul>
        @else
            <p>{{ __('لأي سؤال أو طلب بخصوص بياناتك الشخصية، تواصل مع إدارة سمسار عبر التطبيق أو الموقع.') }}</p>
        @endif
    </article>

    @push('head')
        <style>
            .legal h2 { margin-top: 26px; font-size: 17px; }
            .legal p, .legal li { line-height: 1.85; }
            .legal ul { padding-inline-start: 22px; }
            .legal li { margin-bottom: 6px; }
        </style>
    @endpush
@endsection
