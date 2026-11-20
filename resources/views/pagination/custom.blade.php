@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Страницы">
        <ul>
            @if ($paginator->onFirstPage())
                <li><span aria-disabled="true">« Назад</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">« Назад</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span>{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Вперёд »</a></li>
            @else
                <li><span aria-disabled="true">Вперёд »</span></li>
            @endif
        </ul>
    </nav>
@endif
