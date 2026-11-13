@extends('site.layout')
@section('title', 'تعديل الإعلان')
@section('content')
    <div class="section" style="margin-top:20px; max-width: 760px; margin-inline: auto;">
        <h1>تعديل الإعلان</h1>
        <p class="muted" style="margin-top:0">أي تعديل يُعيد الإعلان إلى المراجعة قبل نشره مجددًا.</p>

        <div class="card" style="margin-bottom:16px">
            <h2>الصور</h2>
            <div class="row">
                @foreach ($ad->images as $image)
                    <div style="position:relative">
                        <img src="{{ $image->url }}" alt="" style="width:96px;height:76px;object-fit:cover;border-radius:10px;border:2px solid {{ $image->is_cover ? 'var(--teal)' : 'transparent' }}">
                        <form method="POST" action="{{ route('site.my.ads.images.destroy', [$ad, $image]) }}" onsubmit="return confirm('حذف هذه الصورة؟');" style="position:absolute;top:4px;inset-inline-end:4px">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm" style="padding:2px 7px" aria-label="حذف الصورة">@include('site.partials.icon', ['name' => 'x', 'size' => 12])</button>
                        </form>
                    </div>
                @endforeach
            </div>
            @if ($ad->images->count() < 12)
                <form method="POST" action="{{ route('site.my.ads.images.store', $ad) }}" enctype="multipart/form-data" style="margin-top:12px">
                    @csrf
                    <label for="images">إضافة صور ({{ 12 - $ad->images->count() }} متبقية)</label>
                    <div class="row">
                        <input type="file" id="images" name="images[]" accept="image/*" multiple required style="flex:1">
                        <button class="btn btn-outline btn-sm" type="submit">رفع</button>
                    </div>
                </form>
            @endif
        </div>

        <form class="card" method="POST" action="{{ route('site.my.ads.update', $ad) }}">
            @csrf
            <h2>التفاصيل</h2>
            <p class="muted small" style="margin:0">الفئة: {{ $ad->category->name_ar }} — لا يمكن تغيير الفئة أو الموقع بعد النشر؛ أنشئ إعلانًا جديدًا إن لزم.</p>

            <label for="title">العنوان</label>
            <input type="text" id="title" name="title" value="{{ old('title', $ad->title) }}" required maxlength="150">

            <label for="price">السعر ({{ $ad->currency }}) — اتركه فارغًا لـ "السعر عند التواصل"</label>
            <input type="number" id="price" name="price" value="{{ old('price', $ad->price !== null ? (float) $ad->price : '') }}" min="0" step="any">

            @foreach ($ad->category->attributes_ as $attr)
                @include('site.partials.attribute-field', ['attr' => $attr, 'value' => $values[$attr->key] ?? null, 'enforceRequired' => false])
            @endforeach

            <label for="description">الوصف</label>
            <textarea id="description" name="description" rows="5" required maxlength="5000">{{ old('description', $ad->description) }}</textarea>

            <div class="row" style="margin-top:16px">
                <button class="btn btn-primary" type="submit">حفظ التعديلات</button>
                <a class="btn btn-muted" href="{{ route('site.my.ads') }}">إلغاء</a>
            </div>
        </form>
    </div>
@endsection
