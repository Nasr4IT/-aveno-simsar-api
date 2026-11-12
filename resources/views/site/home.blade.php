@extends('site.layout')
@section('title', 'الرئيسية')
@section('content')
    <section class="hero">
        <h1>@auth صباح الخير، {{ auth()->user()->name }} 👋 @else أهلاً بك في Semsar @endauth</h1>
        <p class="muted" style="margin:0">اعثر على أفضل العروض بالقرب منك.</p>
        <form method="GET" action="{{ route('site.explore') }}">
            @include('site.partials.icon', ['name' => 'search', 'size' => 20])
            <input type="text" name="q" placeholder="عمّ تبحث؟ سيارة، شقة، دراجة..." aria-label="بحث">
        </form>
    </section>

    @if ($banners->isNotEmpty())
        <section class="section">
            <div class="banners">
                @foreach ($banners as $banner)
                    @if ($banner->link_url)
                        <a href="{{ $banner->link_url }}" target="_blank" rel="noopener"><img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? 'إعلان' }}"></a>
                    @else
                        <div><img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? 'إعلان' }}"></div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="cat-tiles">
            @foreach ($categories as $category)
                @include('site.partials.category-tile', ['category' => $category])
            @endforeach
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="section">
            <div class="section-head">
                <h2>إعلانات مميّزة</h2>
                <a href="{{ route('site.explore') }}">عرض الكل</a>
            </div>
            <div class="rail">
                @foreach ($featured as $ad)
                    @include('site.partials.ad-card', ['ad' => $ad])
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="section-head">
            <h2>أحدث الإعلانات</h2>
            <a href="{{ route('site.explore') }}">عرض الكل</a>
        </div>
        @if ($latest->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'tag', 'size' => 40])
                لا توجد إعلانات منشورة بعد — كن أول من ينشر!
            </div>
        @else
            <div class="ad-grid">
                @foreach ($latest as $ad)
                    @include('site.partials.ad-card', ['ad' => $ad])
                @endforeach
            </div>
        @endif
    </section>
@endsection
