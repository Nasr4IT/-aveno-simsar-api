@extends('site.layout')
@section('title', __('بيع غرضك'))
@section('content')
    <div class="section" style="margin-top:20px; max-width: 760px; margin-inline: auto;">
        <h1>{{ __('بيع غرضك') }}</h1>
        <p class="muted" style="margin-top:0">{{ __('سيظهر إعلانك للجميع بعد مراجعته من الإدارة.') }}</p>

        <div class="card" style="margin-bottom:16px">
            <h2 class="row"><span class="step">1</span> {{ __('اختر الفئة') }}</h2>
            <div class="cat-tiles">
                @foreach ($categories as $category)
                    @php $isSelected = $selected && ($selected->id === $category->id || $selected->parent_id === $category->id); @endphp
                    <a class="cat-tile" style="{{ $isSelected ? 'border-color:var(--teal); background:var(--teal-soft);' : '' }}" href="{{ route('site.sell', ['category_id' => $category->id]) }}">
                        <div class="icon">@include('site.partials.icon', ['name' => match ($category->slug) { 'cars' => 'car', 'real-estate' => 'house', 'motorcycles' => 'bike', default => 'box' }, 'size' => 24])</div>
                        <div><strong>{{ $category->localized('name') }}</strong></div>
                        @if ($isSelected) <span style="margin-inline-start:auto;color:var(--teal-dark)">@include('site.partials.icon', ['name' => 'check', 'size' => 18, 'class' => 'pop'])</span> @endif
                    </a>
                @endforeach
            </div>

            @if ($selected && ($selected->children->isNotEmpty() || $selected->parent))
                @php $siblings = $selected->parent ? $selected->parent->children : $selected->children; @endphp
                <label>{{ __('الفئة الفرعية') }}</label>
                <div class="chips">
                    @foreach ($siblings as $child)
                        <a class="chip {{ $selected->id === $child->id ? 'active' : '' }}" href="{{ route('site.sell', ['category_id' => $child->id]) }}">{{ $child->localized('name') }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($selected)
            <form method="POST" action="{{ route('site.sell.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="category_id" value="{{ $selected->id }}">

                <div class="card" style="margin-bottom:16px">
                    <h2 class="row"><span class="step">2</span> {{ __('أضف الصور') }}</h2>
                    <label for="images">{{ __('حتى 12 صورة (JPG أو PNG، بحد أقصى 5MB لكل صورة) — الأولى هي صورة الغلاف') }}</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                    <div class="row previews" id="image-previews" style="margin-top:10px"></div>
                </div>

                <div class="card" style="margin-bottom:16px">
                    <h2 class="row"><span class="step">3</span> {{ __('التفاصيل') }}</h2>

                    <label for="title">{{ __('العنوان') }}</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="150" placeholder="{{ __('مثال: تويوتا كورولا 2019') }}">

                    <div class="form-grid">
                        <div>
                            <label for="price">{{ __('السعر (اختياري)') }}</label>
                            <input type="number" id="price" name="price" value="{{ old('price') }}" min="0" step="any">
                        </div>
                        <div>
                            <label for="currency">{{ __('العملة') }}</label>
                            <select id="currency" name="currency">
                                <option value="USD" @selected(old('currency', 'USD') === 'USD')>{{ __('دولار أمريكي (USD)') }}</option>
                                <option value="SYP" @selected(old('currency') === 'SYP')>{{ __('ليرة سورية (SYP)') }}</option>
                            </select>
                        </div>
                    </div>

                    @include('site.partials.location-select', ['governorates' => $governorates, 'required' => true])
                    @include('site.partials.ad-options')

                    @foreach ($selected->attributes_ as $attr)
                        @include('site.partials.attribute-field', ['attr' => $attr, 'enforceRequired' => true])
                    @endforeach

                    <label for="description">{{ __('الوصف') }}</label>
                    <textarea id="description" name="description" rows="5" required maxlength="5000" placeholder="{{ __('الحالة، سبب البيع، أي تفاصيل تهم المشتري...') }}">{{ old('description') }}</textarea>
                </div>

                <button class="btn btn-primary btn-block" type="submit" style="padding:14px">{{ __('نشر الإعلان') }}</button>
            </form>
        @else
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'tag', 'size' => 40])
                {{ __('اختر فئة أولاً لتظهر حقول الإعلان الخاصة بها.') }}
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            var input = document.getElementById('images');
            if (input) input.addEventListener('change', function () {
                var box = document.getElementById('image-previews'); box.innerHTML = '';
                Array.prototype.slice.call(input.files, 0, 12).forEach(function (file, i) {
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(file);
                    img.style.animationDelay = (i * 50) + 'ms';
                    box.appendChild(img);
                });
            });
        </script>
    @endpush
@endsection
