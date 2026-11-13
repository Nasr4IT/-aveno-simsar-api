{{-- Minimal pager so the site's CSS stays self-contained (Laravel's default paginator view assumes Tailwind). --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="{{ __('الصفحات') }}">
        @if ($paginator->onFirstPage())
            <span class="disabled">{{ __('السابق') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('السابق') }}</a>
        @endif

        @foreach (range(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page)
            @if ($page === $paginator->currentPage())
                <span class="current">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('التالي') }}</a>
        @else
            <span class="disabled">{{ __('التالي') }}</span>
        @endif
    </nav>
@endif
