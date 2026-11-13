@extends('site.layout')
@section('title', __('الرئيسية'))
@section('content')
    <section class="hero">
        <h1>
            @auth
                {{ __('أهلاً، :name', ['name' => auth()->user()->name]) }} <span class="wave">👋</span>
            @else
                {{ __('أهلاً بك في Semsar') }}
            @endauth
        </h1>
        <p>{{ __('اعثر على أفضل العروض بالقرب منك.') }}</p>
        <form method="GET" action="{{ route('site.explore') }}">
            @include('site.partials.icon', ['name' => 'search', 'size' => 20])
            <input type="text" name="q" placeholder="{{ __('عمّ تبحث؟ سيارة، شقة، دراجة...') }}" aria-label="{{ __('بحث') }}">
        </form>
    </section>

    @if ($banners->isNotEmpty())
        <section class="section">
            <div class="banners">
                @foreach ($banners as $banner)
                    @if ($banner->link_url)
                        <a href="{{ $banner->link_url }}" target="_blank" rel="noopener"><img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? __('إعلان') }}"></a>
                    @else
                        <div><img src="{{ $banner->image_url }}" alt="{{ $banner->title ?? __('إعلان') }}"></div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section class="section">
        <div class="section-head">
            <h2>{{ __('تصفّح الفئات') }}</h2>
        </div>
        <div class="cat-tiles">
            @foreach ($categories as $category)
                @include('site.partials.category-tile', ['category' => $category])
            @endforeach
        </div>
    </section>

    @if ($featured->isNotEmpty())
        <section class="section">
            <div class="section-head">
                <h2>{{ __('إعلانات مميّزة') }}</h2>
                <a href="{{ route('site.explore') }}">{{ __('عرض الكل') }} @include('site.partials.icon', ['name' => 'arrow', 'size' => 14])</a>
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
            <h2>{{ __('أحدث الإعلانات') }}</h2>
            <a href="{{ route('site.explore') }}">{{ __('عرض الكل') }} @include('site.partials.icon', ['name' => 'arrow', 'size' => 14])</a>
        </div>
        @if ($latest->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'tag', 'size' => 40])
                {{ __('لا توجد إعلانات منشورة بعد — كن أول من ينشر!') }}
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
