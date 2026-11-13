@extends('site.layout')
@section('title', $ad->title)
@section('content')
    @php $cover = $ad->images->firstWhere('is_cover', true) ?? $ad->images->first(); @endphp

    <div class="detail" style="margin-top:20px">
        <div>
            <div class="gallery">
                <div class="main">
                    @if ($cover)
                        <img id="gallery-main" src="{{ $cover->url }}" alt="{{ $ad->title }}">
                    @else
                        <div class="empty">@include('site.partials.icon', ['name' => 'image', 'size' => 48])</div>
                    @endif
                </div>
                @if ($ad->images->count() > 1)
                    <div class="thumbs">
                        @foreach ($ad->images as $image)
                            <img src="{{ $image->url }}" alt="" class="{{ $image->is($cover) ? 'active' : '' }}" data-full="{{ $image->url }}">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="card" style="margin-top:16px">
                <div class="row" style="justify-content:space-between">
                    <h1 style="margin:0">{{ $ad->title }}</h1>
                    @if ($ad->status === 'sold')
                        <span class="badge badge-sold">مُباع</span>
                    @elseif ($ad->is_featured)
                        <span class="badge badge-approved">مميّز</span>
                    @endif
                </div>
                <div class="price-big">{{ $ad->price !== null ? number_format($ad->price, 0).' '.$ad->currency : 'السعر عند التواصل' }}</div>
                <div class="row muted small" style="margin-top:6px">
                    <span class="row">@include('site.partials.icon', ['name' => 'pin', 'size' => 14]) {{ $ad->city?->name_ar }}، {{ $ad->governorate?->name_ar }}</span>
                    <span class="row">@include('site.partials.icon', ['name' => 'clock', 'size' => 14]) نُشر {{ ($ad->published_at ?? $ad->created_at)->locale('ar')->diffForHumans() }}</span>
                    <span class="row">@include('site.partials.icon', ['name' => 'eye', 'size' => 14]) {{ number_format($ad->views_count) }} مشاهدة</span>
                    <span class="row">@include('site.partials.icon', ['name' => 'tag', 'size' => 14]) <a href="{{ route('site.explore', ['category_id' => $ad->category_id]) }}">{{ $ad->category?->name_ar }}</a></span>
                </div>

                @if ($ad->attributeValues->isNotEmpty())
                    <h3 style="margin-top:18px">المواصفات</h3>
                    <dl class="kv">
                        @foreach ($ad->attributeValues as $value)
                            @php $decoded = json_decode($value->value, true); @endphp
                            <dt>{{ $value->attribute->label_ar }}</dt>
                            <dd>{{ is_array($decoded) ? implode('، ', $decoded) : ($value->attribute->type === 'boolean' ? ($value->value ? 'نعم' : 'لا') : $value->value) }}</dd>
                        @endforeach
                    </dl>
                @endif

                <h3 style="margin-top:18px">الوصف</h3>
                <p style="white-space:pre-line; margin:0">{{ $ad->description }}</p>
            </div>
        </div>

        <aside>
            <div class="card">
                <a class="seller" href="{{ route('site.users.show', $ad->user) }}">
                    <div class="avatar">{{ mb_substr($ad->user->name, 0, 1) }}</div>
                    <div>
                        <strong>{{ $ad->user->name }}</strong>
                        <div>@include('site.partials.rating-stars', ['average' => $ad->user->rating_average, 'count' => $ad->user->rating_count])</div>
                    </div>
                </a>
                <div class="actions">
                    @auth
                        @if ($ad->user_id === auth()->id())
                            <a class="btn btn-outline" href="{{ route('site.my.ads') }}">هذا إعلانك — إدارة إعلاناتي</a>
                        @else
                            @if ($ad->status === 'approved')
                                <form method="POST" action="{{ route('site.ads.message', $ad) }}">
                                    @csrf
                                    <button class="btn btn-primary btn-block" type="submit">@include('site.partials.icon', ['name' => 'chat', 'size' => 18]) مراسلة البائع</button>
                                </form>
                                <form method="POST" action="{{ route($isFavorited ? 'site.ads.unfavorite' : 'site.ads.favorite', $ad) }}">
                                    @csrf
                                    <button class="btn btn-outline btn-block" type="submit">@include('site.partials.icon', ['name' => 'heart', 'size' => 18, 'filled' => $isFavorited]) {{ $isFavorited ? 'إزالة من المفضلة' : 'حفظ في المفضلة' }}</button>
                                </form>
                            @endif
                            @include('site.partials.report-form', ['action' => route('site.ads.report', $ad), 'label' => 'الإبلاغ عن هذا الإعلان'])
                        @endif
                    @else
                        <a class="btn btn-primary btn-block" href="{{ route('site.login') }}">@include('site.partials.icon', ['name' => 'chat', 'size' => 18]) سجّل الدخول للمراسلة</a>
                    @endauth
                </div>
                <p class="muted small" style="margin:12px 0 0">
                    @include('site.partials.icon', ['name' => 'heart', 'size' => 13]) {{ $ad->favorited_by_count }} أضافوه للمفضلة
                </p>
            </div>
        </aside>
    </div>

    @if ($similar->isNotEmpty())
        <section class="section">
            <div class="section-head"><h2>إعلانات مشابهة</h2></div>
            <div class="rail">
                @foreach ($similar as $other)
                    @include('site.partials.ad-card', ['ad' => $other])
                @endforeach
            </div>
        </section>
    @endif

    @push('scripts')
        <script>
            document.querySelectorAll('.gallery .thumbs img').forEach(function (thumb) {
                thumb.addEventListener('click', function () {
                    document.getElementById('gallery-main').src = thumb.dataset.full;
                    document.querySelectorAll('.gallery .thumbs img').forEach(function (t) { t.classList.remove('active'); });
                    thumb.classList.add('active');
                });
            });
        </script>
    @endpush
@endsection
