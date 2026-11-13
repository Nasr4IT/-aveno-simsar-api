@extends('site.layout')
@section('title', 'بيع غرضك')
@section('content')
    <div class="section" style="margin-top:20px; max-width: 760px; margin-inline: auto;">
        <h1>بيع غرضك</h1>
        <p class="muted" style="margin-top:0">سيظهر إعلانك للجميع بعد مراجعته من الإدارة.</p>

        <div class="card" style="margin-bottom:16px">
            <h2 class="row"><span class="avatar" style="width:30px;height:30px;font-size:14px">1</span> اختر الفئة</h2>
            <div class="cat-tiles">
                @foreach ($categories as $category)
                    @php $isSelected = $selected && ($selected->id === $category->id || $selected->parent_id === $category->id); @endphp
                    <a class="cat-tile" style="{{ $isSelected ? 'border-color:var(--teal); background:var(--teal-soft);' : '' }}" href="{{ route('site.sell', ['category_id' => $category->id]) }}">
                        <div class="icon">@include('site.partials.icon', ['name' => match ($category->slug) { 'cars' => 'car', 'real-estate' => 'house', 'motorcycles' => 'bike', default => 'box' }, 'size' => 24])</div>
                        <div><strong>{{ $category->name_ar }}</strong></div>
                        @if ($isSelected) <span style="margin-inline-start:auto;color:var(--teal-dark)">@include('site.partials.icon', ['name' => 'check', 'size' => 18])</span> @endif
                    </a>
                @endforeach
            </div>

            @if ($selected && ($selected->children->isNotEmpty() || $selected->parent))
                @php $siblings = $selected->parent ? $selected->parent->children : $selected->children; @endphp
                <label>الفئة الفرعية</label>
                <div class="chips">
                    @foreach ($siblings as $child)
                        <a class="chip {{ $selected->id === $child->id ? 'active' : '' }}" href="{{ route('site.sell', ['category_id' => $child->id]) }}">{{ $child->name_ar }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($selected)
            <form method="POST" action="{{ route('site.sell.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="category_id" value="{{ $selected->id }}">

                <div class="card" style="margin-bottom:16px">
                    <h2 class="row"><span class="avatar" style="width:30px;height:30px;font-size:14px">2</span> أضف الصور</h2>
                    <label for="images">حتى 12 صورة (JPG أو PNG، بحد أقصى 5MB لكل صورة) — الأولى هي صورة الغلاف</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple required>
                    <div class="row" id="image-previews" style="margin-top:10px"></div>
                </div>

                <div class="card" style="margin-bottom:16px">
                    <h2 class="row"><span class="avatar" style="width:30px;height:30px;font-size:14px">3</span> التفاصيل</h2>

                    <label for="title">العنوان</label>
                    <input type="text" id="title" name="title" value="{{ old('title') }}" required maxlength="150" placeholder="مثال: تويوتا كورولا 2019">

                    <div class="form-grid">
                        <div>
                            <label for="price">السعر (اختياري)</label>
                            <input type="number" id="price" name="price" value="{{ old('price') }}" min="0" step="any">
                        </div>
                        <div>
                            <label for="currency">العملة</label>
                            <select id="currency" name="currency">
                                <option value="USD" @selected(old('currency', 'USD') === 'USD')>دولار أمريكي (USD)</option>
                                <option value="SYP" @selected(old('currency') === 'SYP')>ليرة سورية (SYP)</option>
                            </select>
                        </div>
                    </div>

                    @include('site.partials.location-select', ['governorates' => $governorates, 'required' => true])

                    @foreach ($selected->attributes_ as $attr)
                        @include('site.partials.attribute-field', ['attr' => $attr, 'enforceRequired' => true])
                    @endforeach

                    <label for="description">الوصف</label>
                    <textarea id="description" name="description" rows="5" required maxlength="5000" placeholder="الحالة، سبب البيع، أي تفاصيل تهم المشتري...">{{ old('description') }}</textarea>
                </div>

                <button class="btn btn-primary btn-block" type="submit" style="padding:14px">نشر الإعلان</button>
            </form>
        @else
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'tag', 'size' => 40])
                اختر فئة أولاً لتظهر حقول الإعلان الخاصة بها.
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            var input = document.getElementById('images');
            if (input) input.addEventListener('change', function () {
                var box = document.getElementById('image-previews'); box.innerHTML = '';
                Array.prototype.slice.call(input.files, 0, 12).forEach(function (file) {
                    var img = document.createElement('img');
                    img.src = URL.createObjectURL(file); img.style.cssText = 'width:72px;height:72px;object-fit:cover;border-radius:10px';
                    box.appendChild(img);
                });
            });
        </script>
    @endpush
@endsection
