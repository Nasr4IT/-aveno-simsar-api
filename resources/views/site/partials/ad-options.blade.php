{{-- The publish-form options (same fields as the API's is_negotiable / address / allow_chat / allow_calls / hide_phone). Optional $ad for current values. Each checkbox is preceded by a hidden "0" so unticking saves as off. --}}
@php
    $value = fn (string $field, $default) => old($field, isset($ad) ? (int) $ad->{$field} : $default);
@endphp
<label for="address">{{ __('العنوان التفصيلي (اختياري)') }}</label>
<input type="text" id="address" name="address" maxlength="255" value="{{ old('address', $ad->address ?? '') }}" placeholder="{{ __('مثال: المزة، شارع الجلاء') }}">

<div style="display:grid; gap:2px; margin-top:8px">
    @foreach ([
        'is_negotiable' => [__('السعر قابل للتفاوض'), 0],
        'allow_chat' => [__('السماح بالمراسلة'), 1],
        'allow_calls' => [__('السماح بالاتصال'), 1],
        'hide_phone' => [__('إخفاء رقم هاتفي'), 0],
    ] as $field => [$label, $default])
        <input type="hidden" name="{{ $field }}" value="0">
        <label class="inline"><input type="checkbox" name="{{ $field }}" value="1" @checked((string) $value($field, $default) === '1')> {{ $label }}</label>
    @endforeach
    <span class="muted small">{{ __('يظهر رقمك للمستخدمين المسجّلين فقط عند السماح بالاتصال وعدم إخفاء الرقم.') }}</span>
</div>
