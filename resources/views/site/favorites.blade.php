@extends('site.layout')
@section('title', __('المفضلة'))
@section('content')
    <div class="section" style="margin-top:20px">
        <h1>{{ __('المفضلة') }}</h1>
        @if ($ads->isEmpty())
            <div class="card empty">
                @include('site.partials.icon', ['name' => 'heart', 'size' => 40])
                {{ __('لم تحفظ أي إعلان بعد — اضغط "حفظ في المفضلة" على أي إعلان يعجبك.') }}
            </div>
        @else
            <div class="ad-grid">
                @foreach ($ads as $ad)
                    @include('site.partials.ad-card', ['ad' => $ad])
                @endforeach
            </div>
            @include('site.partials.pagination', ['paginator' => $ads])
        @endif
    </div>
@endsection
