{{-- Five stars for a user's rating_average (0–5), plus the count. --}}
@php $filled = (int) round($average ?? 0); @endphp
<span class="stars" title="{{ __(':score من 5', ['score' => number_format($average ?? 0, 1)]) }}">
    @for ($i = 1; $i <= 5; $i++)
        <span class="ico {{ $i <= $filled ? '' : 'off' }}">@include('site.partials.icon', ['name' => 'star', 'size' => 15, 'filled' => $i <= $filled])</span>
    @endfor
</span>
@isset($count)
    <span class="muted small">({{ $count }})</span>
@endisset
