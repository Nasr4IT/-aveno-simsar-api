{{-- Outline icon set for the public site (stroke-based, matches the app's icons). Usage: @include('site.partials.icon', ['name' => 'home']) --}}
@php
    $paths = [
        'home' => '<path d="M4 11.5 12 5l8 6.5" /><path d="M6 10v9a1 1 0 0 0 1 1h3v-5h4v5h3a1 1 0 0 0 1-1v-9" />',
        'search' => '<circle cx="11" cy="11" r="6.5" /><path d="m20 20-4.3-4.3" />',
        'plus' => '<path d="M12 5v14M5 12h14" />',
        'chat' => '<path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l1-4.1A8 8 0 1 1 20 12z" />',
        'user' => '<circle cx="12" cy="8" r="3.6" /><path d="M5 20c0-3.6 3.1-6 7-6s7 2.4 7 6" />',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z" />',
        'bell' => '<path d="M6 16V11a6 6 0 0 1 12 0v5l1.5 2h-15z" /><path d="M10 20a2 2 0 0 0 4 0" />',
        'car' => '<path d="M5 15l1.5-5A2 2 0 0 1 8.4 8.5h7.2a2 2 0 0 1 1.9 1.5L19 15" /><rect x="3.5" y="15" width="17" height="4" rx="1.5" /><circle cx="7.5" cy="19" r="1.3" /><circle cx="16.5" cy="19" r="1.3" />',
        'house' => '<path d="M4 11.5 12 5l8 6.5" /><path d="M6 10v9a1 1 0 0 0 1 1h3v-5h4v5h3a1 1 0 0 0 1-1v-9" />',
        'bike' => '<circle cx="6" cy="16" r="3.2" /><circle cx="18" cy="16" r="3.2" /><path d="M6 16l3.5-7h5L18 16M9.5 9h-2M12 16l2.5-7" />',
        'box' => '<path d="m12 3 8 4.5v9L12 21l-8-4.5v-9z" /><path d="M4 7.5 12 12l8-4.5M12 12v9" />',
        'pin' => '<path d="M12 21s-6-5.6-6-10.5a6 6 0 0 1 12 0C18 15.4 12 21 12 21z" /><circle cx="12" cy="10.5" r="2" />',
        'clock' => '<circle cx="12" cy="12" r="8" /><path d="M12 8v4.5l3 1.5" />',
        'eye' => '<path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" /><circle cx="12" cy="12" r="2.8" />',
        'star' => '<path d="m12 3.5 2.6 5.4 5.9.8-4.3 4.1 1 5.9L12 17l-5.2 2.7 1-5.9-4.3-4.1 5.9-.8z" />',
        'arrow' => '<path d="M5 12h14M12 5l7 7-7 7" />',
        'filter' => '<path d="M4 6h16M7 12h10M10 18h4" />',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5" /><rect x="13" y="4" width="7" height="7" rx="1.5" /><rect x="4" y="13" width="7" height="7" rx="1.5" /><rect x="13" y="13" width="7" height="7" rx="1.5" />',
        'image' => '<rect x="3.5" y="5" width="17" height="14" rx="2.5" /><circle cx="9" cy="10" r="1.6" /><path d="m5 17 4.5-4.5a2 2 0 0 1 2.8 0L19 19" />',
        'logout' => '<path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4" /><path d="M15 16l4-4-4-4" /><path d="M19 12H9" />',
        'x' => '<path d="M6 6l12 12M18 6 6 18" />',
        'navigate' => '<path d="m4 11 16-7-7 16-2-7z" />',
        'tag' => '<path d="M3.5 12.5V5a1.5 1.5 0 0 1 1.5-1.5h7.5l8 8-9 9z" /><circle cx="8" cy="8" r="1.3" />',
        'list' => '<rect x="4" y="4" width="16" height="16" rx="3" /><path d="M8 9h8M8 13h8M8 17h4" />',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5" />',
        'flag' => '<path d="M6 3v18" /><path d="M6 4.5c2-1 4-1 6 0s4 1 6 0v9c-2 1-4 1-6 0s-4-1-6 0z" />',
        'globe' => '<circle cx="12" cy="12" r="8.5" /><path d="M3.5 12h17M12 3.5c2.4 2.4 3.6 5.2 3.6 8.5s-1.2 6.1-3.6 8.5c-2.4-2.4-3.6-5.2-3.6-8.5S9.6 5.9 12 3.5z" />',
        'moon' => '<path d="M19.5 14.5A7.5 7.5 0 0 1 9.5 4.5a7.5 7.5 0 1 0 10 10z" />',
        'sun' => '<circle cx="12" cy="12" r="3.8" /><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M5.6 18.4 7 17M17 7l1.4-1.4" />',
    ];
    $size = $size ?? 20;
@endphp
<svg class="ico {{ $class ?? '' }}"width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="{{ ($filled ?? false) ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$name] !!}
</svg>
