{{-- Category tile for the home grid. Icon chosen by slug; falls back to a generic box for admin-created categories. --}}
@php
    $icon = match ($category->slug) {
        'cars' => 'car',
        'real-estate' => 'house',
        'motorcycles' => 'bike',
        default => 'box',
    };
@endphp
<a class="cat-tile" href="{{ route('site.explore', ['category_id' => $category->id]) }}">
    <div class="icon">@include('site.partials.icon', ['name' => $icon, 'size' => 24])</div>
    <div>
        <strong>{{ $category->name_ar }}</strong>
        @isset($category->ads_count)
            <span>{{ number_format($category->ads_count) }} إعلان</span>
        @endisset
    </div>
</a>
