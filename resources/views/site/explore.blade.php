@extends('site.layout')
@section('title', __('استكشف'))
@section('content')
    @php $q = fn (array $overrides) => request()->fullUrlWithQuery($overrides + ['page' => null]); @endphp

    <div class="section" style="margin-top:20px">
        <h1>{{ __('استكشف') }}</h1>
        <div class="chips">
            <a class="chip {{ empty($filters['category_id']) ? 'active' : '' }}" href="{{ $q(['category_id' => null]) }}">{{ __('الكل') }}</a>
            @foreach ($categories as $category)
                <a class="chip {{ ($filters['category_id'] ?? null) == $category->id ? 'active' : '' }}" href="{{ $q(['category_id' => $category->id]) }}">{{ $category->localized('name') }}</a>
            @endforeach
        </div>
    </div>

    <div class="explore">
        <aside>
            <form class="card" method="GET" action="{{ route('site.explore') }}" id="filters">
                <details class="filters" open>
                    <summary>@include('site.partials.icon', ['name' => 'filter', 'size' => 18]) {{ __('الفلاتر') }}</summary>

                    <label for="f-q">{{ __('بحث') }}</label>
                    <input type="text" id="f-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('كلمة في العنوان أو الوصف') }}">

                    <label for="f-category">{{ __('الفئة') }}</label>
                    <select id="f-category" name="category_id" onchange="this.form.submit()">
                        <option value="">{{ __('كل الفئات') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>{{ $category->localized('name') }}</option>
                            @foreach ($category->children as $child)
                                <option value="{{ $child->id }}" @selected(($filters['category_id'] ?? null) == $child->id)>— {{ $child->localized('name') }}</option>
                            @endforeach
                        @endforeach
                    </select>

                    @include('site.partials.location-select', ['governorates' => $governorates])

                    <div class="form-grid">
                        <div>
                            <label for="f-min">{{ __('السعر من') }}</label>
                            <input type="number" id="f-min" name="min_price" min="0" value="{{ $filters['min_price'] ?? '' }}">
                        </div>
                        <div>
                            <label for="f-max">{{ __('إلى') }}</label>
                            <input type="number" id="f-max" name="max_price" min="0" value="{{ $filters['max_price'] ?? '' }}">
                        </div>
                    </div>

                    @foreach ($attributeFilters as $attr)
                        <label for="f-{{ $attr->key }}">{{ $attr->localized('label') }}</label>
                        @if (in_array($attr->type, ['select', 'multiselect'], true))
                            <select id="f-{{ $attr->key }}" name="{{ $attr->key }}">
                                <option value="">{{ __('الكل') }}</option>
                                @foreach ($attr->options ?? [] as $option)
                                    <option value="{{ $option }}" @selected(request($attr->key) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif ($attr->type === 'boolean')
                            <select id="f-{{ $attr->key }}" name="{{ $attr->key }}">
                                <option value="">{{ __('الكل') }}</option>
                                <option value="1" @selected(request($attr->key) === '1')>{{ __('نعم') }}</option>
                                <option value="0" @selected(request($attr->key) === '0')>{{ __('لا') }}</option>
                            </select>
                        @else
                            <input type="{{ $attr->type === 'number' ? 'number' : 'text' }}" id="f-{{ $attr->key }}" name="{{ $attr->key }}" value="{{ request($attr->key) }}">
                        @endif
                    @endforeach

                    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}" id="f-sort">
                    <input type="hidden" name="lat" value="{{ $filters['lat'] ?? '' }}" id="f-lat">
                    <input type="hidden" name="lng" value="{{ $filters['lng'] ?? '' }}" id="f-lng">

                    <div class="actions">
                        <button class="btn btn-primary btn-block" type="submit">{{ __('تطبيق') }}</button>
                        <button class="btn btn-outline btn-block" type="button" id="nearest-btn">@include('site.partials.icon', ['name' => 'navigate', 'size' => 16]) {{ __('الأقرب إليّ') }}</button>
                        <a class="btn btn-muted btn-block" href="{{ route('site.explore') }}">{{ __('مسح الفلاتر') }}</a>
                    </div>
                </details>
            </form>
        </aside>

        <section>
            <div class="results-head">
                <span class="muted">{{ trans_choice('{0} لا توجد نتائج|{1} نتيجة واحدة|[2,*] :count نتيجة', $ads->total(), ['count' => number_format($ads->total())]) }}</span>
                <select aria-label="{{ __('الترتيب') }}" onchange="document.getElementById('f-sort').value=this.value; document.getElementById('filters').requestSubmit();">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('الأحدث أولاً') }}</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>{{ __('السعر: من الأقل') }}</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>{{ __('السعر: من الأعلى') }}</option>
                    <option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>{{ __('الأكثر مشاهدة') }}</option>
                    @if (($filters['sort'] ?? '') === 'nearest')
                        <option value="nearest" selected>{{ __('الأقرب إليّ') }}</option>
                    @endif
                </select>
            </div>

            @if ($ads->isEmpty())
                <div class="card empty">
                    @include('site.partials.icon', ['name' => 'search', 'size' => 40])
                    {{ __('لا توجد نتائج مطابقة — جرّب كلمات أخرى أو خفّف الفلاتر.') }}
                </div>
            @else
                <div class="ad-grid">
                    @foreach ($ads as $ad)
                        @include('site.partials.ad-card', ['ad' => $ad])
                    @endforeach
                </div>
                @include('site.partials.pagination', ['paginator' => $ads])
            @endif
        </section>
    </div>

    @push('scripts')
        <script>
            // "Nearest to me": fill lat/lng from the browser's geolocation and
            // submit with sort=nearest — the same parameters the app sends.
            document.getElementById('nearest-btn').addEventListener('click', function () {
                if (!navigator.geolocation) { alert({{ Js::from(__('المتصفح لا يدعم تحديد الموقع.')) }}); return; }
                var btn = this; btn.disabled = true;
                navigator.geolocation.getCurrentPosition(function (pos) {
                    document.getElementById('f-lat').value = pos.coords.latitude.toFixed(6);
                    document.getElementById('f-lng').value = pos.coords.longitude.toFixed(6);
                    document.getElementById('f-sort').value = 'nearest';
                    document.getElementById('filters').requestSubmit();
                }, function () { btn.disabled = false; alert({{ Js::from(__('تعذّر تحديد موقعك.')) }}); });
            });
        </script>
    @endpush
@endsection
