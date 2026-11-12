@extends('site.layout')
@section('title', 'استكشف')
@section('content')
    @php $q = fn (array $overrides) => request()->fullUrlWithQuery($overrides + ['page' => null]); @endphp

    <div class="section" style="margin-top:20px">
        <h1>استكشف</h1>
        <div class="chips">
            <a class="chip {{ empty($filters['category_id']) ? 'active' : '' }}" href="{{ $q(['category_id' => null]) }}">الكل</a>
            @foreach ($categories as $category)
                <a class="chip {{ ($filters['category_id'] ?? null) == $category->id ? 'active' : '' }}" href="{{ $q(['category_id' => $category->id]) }}">{{ $category->name_ar }}</a>
            @endforeach
        </div>
    </div>

    <div class="explore">
        <aside>
            <form class="card" method="GET" action="{{ route('site.explore') }}" id="filters">
                <details class="filters" open>
                    <summary>@include('site.partials.icon', ['name' => 'filter', 'size' => 18]) الفلاتر</summary>

                    <label for="f-q">بحث</label>
                    <input type="text" id="f-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="كلمة في العنوان أو الوصف">

                    <label for="f-category">الفئة</label>
                    <select id="f-category" name="category_id" onchange="this.form.submit()">
                        <option value="">كل الفئات</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(($filters['category_id'] ?? null) == $category->id)>{{ $category->name_ar }}</option>
                            @foreach ($category->children as $child)
                                <option value="{{ $child->id }}" @selected(($filters['category_id'] ?? null) == $child->id)>— {{ $child->name_ar }}</option>
                            @endforeach
                        @endforeach
                    </select>

                    @include('site.partials.location-select', ['governorates' => $governorates])

                    <div class="form-grid">
                        <div>
                            <label for="f-min">السعر من</label>
                            <input type="number" id="f-min" name="min_price" min="0" value="{{ $filters['min_price'] ?? '' }}">
                        </div>
                        <div>
                            <label for="f-max">إلى</label>
                            <input type="number" id="f-max" name="max_price" min="0" value="{{ $filters['max_price'] ?? '' }}">
                        </div>
                    </div>

                    @foreach ($attributeFilters as $attr)
                        <label for="f-{{ $attr->key }}">{{ $attr->label_ar }}</label>
                        @if (in_array($attr->type, ['select', 'multiselect'], true))
                            <select id="f-{{ $attr->key }}" name="{{ $attr->key }}">
                                <option value="">الكل</option>
                                @foreach ($attr->options ?? [] as $option)
                                    <option value="{{ $option }}" @selected(request($attr->key) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif ($attr->type === 'boolean')
                            <select id="f-{{ $attr->key }}" name="{{ $attr->key }}">
                                <option value="">الكل</option>
                                <option value="1" @selected(request($attr->key) === '1')>نعم</option>
                                <option value="0" @selected(request($attr->key) === '0')>لا</option>
                            </select>
                        @else
                            <input type="{{ $attr->type === 'number' ? 'number' : 'text' }}" id="f-{{ $attr->key }}" name="{{ $attr->key }}" value="{{ request($attr->key) }}">
                        @endif
                    @endforeach

                    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? '' }}" id="f-sort">
                    <input type="hidden" name="lat" value="{{ $filters['lat'] ?? '' }}" id="f-lat">
                    <input type="hidden" name="lng" value="{{ $filters['lng'] ?? '' }}" id="f-lng">

                    <div class="actions">
                        <button class="btn btn-primary btn-block" type="submit">تطبيق</button>
                        <button class="btn btn-outline btn-block" type="button" id="nearest-btn">@include('site.partials.icon', ['name' => 'navigate', 'size' => 16]) الأقرب إليّ</button>
                        <a class="btn btn-muted btn-block" href="{{ route('site.explore') }}">مسح الفلاتر</a>
                    </div>
                </details>
            </form>
        </aside>

        <section>
            <div class="results-head">
                <span class="muted">{{ number_format($ads->total()) }} نتيجة</span>
                <select aria-label="الترتيب" onchange="document.getElementById('f-sort').value=this.value; document.getElementById('filters').requestSubmit();">
                    <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>الأحدث أولاً</option>
                    <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>السعر: من الأقل</option>
                    <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>السعر: من الأعلى</option>
                    <option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>الأكثر مشاهدة</option>
                    @if (($filters['sort'] ?? '') === 'nearest')
                        <option value="nearest" selected>الأقرب إليّ</option>
                    @endif
                </select>
            </div>

            @if ($ads->isEmpty())
                <div class="card empty">
                    @include('site.partials.icon', ['name' => 'search', 'size' => 40])
                    لا توجد نتائج مطابقة — جرّب كلمات أخرى أو خفّف الفلاتر.
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
                if (!navigator.geolocation) { alert('المتصفح لا يدعم تحديد الموقع.'); return; }
                var btn = this; btn.disabled = true;
                navigator.geolocation.getCurrentPosition(function (pos) {
                    document.getElementById('f-lat').value = pos.coords.latitude.toFixed(6);
                    document.getElementById('f-lng').value = pos.coords.longitude.toFixed(6);
                    document.getElementById('f-sort').value = 'nearest';
                    document.getElementById('filters').requestSubmit();
                }, function () { btn.disabled = false; alert('تعذّر تحديد موقعك.'); });
            });
        </script>
    @endpush
@endsection
