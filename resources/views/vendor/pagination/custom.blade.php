@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" style="display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap; margin-top: 1rem; font-family: inherit;">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border-soft, #e2e8f0); background: #f8fafc; color: #94a3b8; font-size: 0.8rem; font-weight: 600; cursor: not-allowed; text-transform: uppercase; user-select: none;">&laquo; PREV</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border-soft, #cbd5e1); background: #ffffff; color: #475569; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">&laquo; PREV</a>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 10px; font-size: 0.8rem; color: #94a3b8; user-select: none;">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 12px; border-radius: 20px; background: #f59e0b; color: #ffffff; font-size: 0.85rem; font-weight: 700; text-decoration: none; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.25); border: 1px solid #f59e0b; user-select: none;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="display: inline-flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 12px; border-radius: 20px; border: 1px solid var(--border-soft, #cbd5e1); background: #ffffff; color: #334155; font-size: 0.85rem; font-weight: 600; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border-soft, #cbd5e1); background: #ffffff; color: #0284c7; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#ffffff'">NEXT &raquo;</a>
        @else
            <span style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border-soft, #e2e8f0); background: #f8fafc; color: #94a3b8; font-size: 0.8rem; font-weight: 600; cursor: not-allowed; text-transform: uppercase; user-select: none;">NEXT &raquo;</span>
        @endif
    </nav>
@endif
