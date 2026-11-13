{{-- One listing card. Expects $ad with images (and optionally city) loaded. --}}
@php $cover = $ad->images->firstWhere('is_cover', true) ?? $ad->images->first(); @endphp
<a class="ad-card" href="{{ route('site.ads.show', $ad) }}">
    <div class="img">
        @if ($cover)
            <img src="{{ $cover->url }}" alt="{{ $ad->title }}" loading="lazy">
        @else
            <div class="noimg">@include('site.partials.icon', ['name' => 'image', 'size' => 36])</div>
        @endif
        @if ($ad->status === 'sold')
            <span class="pill sold">{{ __('مُباع') }}</span>
        @elseif ($ad->is_featured)
            <span class="pill">{{ __('مميّز') }}</span>
        @endif
    </div>
    <div class="body">
        <div class="title">{{ $ad->title }}</div>
        <div class="price">{{ $ad->price !== null ? number_format($ad->price, 0).' '.$ad->currency : __('السعر عند التواصل') }}</div>
        <div class="meta">
            <span>@include('site.partials.icon', ['name' => 'pin', 'size' => 13]) {{ $ad->city?->localized('name') ?? '—' }}</span>
            <span>@include('site.partials.icon', ['name' => 'clock', 'size' => 13]) {{ $ad->created_at->diffForHumans(short: true) }}</span>
        </div>
    </div>
</a>
