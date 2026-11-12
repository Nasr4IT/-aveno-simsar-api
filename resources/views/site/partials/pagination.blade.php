{{-- Minimal pager so the site's CSS stays self-contained (Laravel's default paginator view assumes Tailwind). --}}
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="الصفحات">
        @if ($paginator->onFirstPage())
            <span class="disabled">السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
        @endif

        @foreach (range(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page)
            @if ($page === $paginator->currentPage())
                <span class="current">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
        @else
            <span class="disabled">التالي</span>
        @endif
    </nav>
@endif
