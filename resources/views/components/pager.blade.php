@if ($paginator->hasPages())
    <nav class="hub-pager" aria-label="صفحات النتائج">
        @if ($paginator->onFirstPage())
            <span class="pager-direction is-disabled" aria-disabled="true">@if ($showArrows ?? true)<span aria-hidden="true">→</span> @endifالسابق</span>
        @else
            <a class="pager-direction" href="{{ $paginator->previousPageUrl() }}" rel="prev">@if ($showArrows ?? true)<span aria-hidden="true">→</span> @endifالسابق</a>
        @endif
        <div class="pager-numbers">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pager-gap" aria-hidden="true">{{ $element }}</span>
                @else
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="pager-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="صفحة {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>
        @if ($paginator->hasMorePages())
            <a class="pager-direction" href="{{ $paginator->nextPageUrl() }}" rel="next">التالي@if ($showArrows ?? true) <span aria-hidden="true">←</span>@endif</a>
        @else
            <span class="pager-direction is-disabled" aria-disabled="true">التالي@if ($showArrows ?? true) <span aria-hidden="true">←</span>@endif</span>
        @endif
    </nav>
@endif
