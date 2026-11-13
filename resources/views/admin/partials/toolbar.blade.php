{{-- Language switch + dark-mode toggle (shared with the website: same cookie, same saved theme). --}}
@php $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar'; @endphp
<a class="tool" href="{{ route('site.locale', $otherLocale) }}" hreflang="{{ $otherLocale }}" lang="{{ $otherLocale }}">
    @include('admin.partials.icon', ['name' => 'globe', 'size' => 16]) {{ $otherLocale === 'en' ? 'English' : 'العربية' }}
</a>
<button class="tool theme-toggle" type="button" data-theme-toggle aria-label="{{ __('تبديل الوضع الليلي') }}" title="{{ __('تبديل الوضع الليلي') }}">
    <span class="moon">@include('admin.partials.icon', ['name' => 'moon', 'size' => 16])</span>
    <span class="sun">@include('admin.partials.icon', ['name' => 'sun', 'size' => 16])</span>
</button>
