{{-- Laravel's default links() view is Tailwind markup, and this panel doesn't load Tailwind. --}}
@if ($paginator->hasPages())
    <nav class="pagination">
        @if ($paginator->onFirstPage())
            <span class="disabled">السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
        @endif

        <span>صفحة {{ $paginator->currentPage() }} من {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
        @else
            <span class="disabled">التالي</span>
        @endif
    </nav>
@endif
