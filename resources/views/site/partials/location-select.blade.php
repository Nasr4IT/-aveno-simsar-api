{{-- Governorate + dependent city selects. Expects $governorates (with cities loaded); optional $selectedGovernorate / $selectedCity / $required. --}}
@php
    $selectedGovernorate = $selectedGovernorate ?? old('governorate_id', request('governorate_id'));
    $selectedCity = $selectedCity ?? old('city_id', request('city_id'));
    $required = $required ?? false;
@endphp
<label for="governorate_id">{{ __('المحافظة') }}</label>
<select id="governorate_id" name="governorate_id" data-location-governorate {{ $required ? 'required' : '' }}>
    <option value="">{{ __('— اختر —') }}</option>
    @foreach ($governorates as $governorate)
        <option value="{{ $governorate->id }}" @selected((string) $selectedGovernorate === (string) $governorate->id)>{{ $governorate->localized('name') }}</option>
    @endforeach
</select>

<label for="city_id">{{ __('المدينة') }}</label>
<select id="city_id" name="city_id" data-location-city {{ $required ? 'required' : '' }}>
    <option value="">{{ __('— اختر —') }}</option>
    @foreach ($governorates as $governorate)
        @foreach ($governorate->cities as $city)
            <option value="{{ $city->id }}" data-governorate="{{ $governorate->id }}" @selected((string) $selectedCity === (string) $city->id)>{{ $city->localized('name') }}</option>
        @endforeach
    @endforeach
</select>

@once
    @push('scripts')
        <script>
            // Show only the cities of the chosen governorate (plain DOM, no framework).
            document.querySelectorAll('[data-location-governorate]').forEach(function (gov) {
                var form = gov.closest('form') || document;
                var city = form.querySelector('[data-location-city]');
                if (!city) return;
                function sync() {
                    Array.prototype.forEach.call(city.options, function (opt) {
                        if (!opt.value) return;
                        var show = !gov.value || opt.dataset.governorate === gov.value;
                        opt.hidden = !show;
                        if (!show && opt.selected) city.value = '';
                    });
                }
                gov.addEventListener('change', sync);
                sync();
            });
        </script>
    @endpush
@endonce
