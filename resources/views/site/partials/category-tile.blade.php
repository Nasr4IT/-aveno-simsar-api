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
        <strong>{{ $category->localized('name') }}</strong>
        @isset($category->ads_count)
            <span>{{ trans_choice('{0} لا إعلانات|{1} إعلان واحد|[2,*] :count إعلان', $category->ads_count, ['count' => number_format($category->ads_count)]) }}</span>
        @endisset
    </div>
</a>
