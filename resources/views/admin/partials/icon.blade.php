{{-- Minimal outline icon set (stroke-based, matches the app's icon style). Usage: @include('admin.partials.icon', ['name' => 'home']) --}}
@php
    $paths = [
        'home' => '<path d="M4 11.5 12 5l8 6.5" /><path d="M6 10v9a1 1 0 0 0 1 1h3v-5h4v5h3a1 1 0 0 0 1-1v-9" />',
        'ads' => '<rect x="4" y="4" width="16" height="16" rx="3" /><path d="M8 9h8M8 13h8M8 17h4" />',
        'users' => '<circle cx="9" cy="8" r="3" /><path d="M3 20c0-3 2.7-5 6-5s6 2 6 5" /><circle cx="17" cy="9" r="2.3" /><path d="M15.5 20c.3-2.3 1.9-4 3.8-4.4" />',
        'grid' => '<rect x="4" y="4" width="7" height="7" rx="1.5" /><rect x="13" y="4" width="7" height="7" rx="1.5" /><rect x="4" y="13" width="7" height="7" rx="1.5" /><rect x="13" y="13" width="7" height="7" rx="1.5" />',
        'image' => '<rect x="3.5" y="5" width="17" height="14" rx="2.5" /><circle cx="9" cy="10" r="1.6" /><path d="m5 17 4.5-4.5a2 2 0 0 1 2.8 0L19 19" />',
        'flag' => '<path d="M6 3v18" /><path d="M6 4.5c2-1 4-1 6 0s4 1 6 0v9c-2 1-4 1-6 0s-4-1-6 0z" />',
        'logout' => '<path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4" /><path d="M15 16l4-4-4-4" /><path d="M19 12H9" />',
    ];
@endphp
<svg class="nav-icon" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    {!! $paths[$name] !!}
</svg>
