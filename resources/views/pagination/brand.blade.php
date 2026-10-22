@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex flex-col gap-1 border-t border-gray-100 px-4 py-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
        <p class="order-2 text-center text-xs text-gray-500 sm:order-1 sm:text-start">
            Showing <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            of <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
        </p>

        <div class="order-1 flex w-full items-center justify-between text-[13px] sm:order-2 sm:w-auto sm:justify-end sm:gap-1">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-gray-200 px-3 text-gray-400 sm:border-0 sm:px-2.5">
                    <span aria-hidden="true">&larr;</span> Prev
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" wire:click.prevent="previousPage" rel="prev" aria-label="{{ __('pagination.previous') }}" class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-gray-200 px-3 font-medium text-gray-600 hover:bg-gray-50 sm:border-0 sm:px-2.5">
                    <span aria-hidden="true">&larr;</span> Prev
                </a>
            @endif

            <span class="text-xs font-semibold text-gray-700 sm:hidden">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </span>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="hidden min-h-9 items-center px-1.5 text-[13px] text-gray-400 sm:inline-flex">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="hidden min-h-9 min-w-9 items-center justify-center rounded-lg bg-brand-600 px-2.5 font-semibold text-white sm:inline-flex">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" wire:click.prevent="gotoPage({{ $page }})" class="hidden min-h-9 min-w-9 items-center justify-center rounded-lg px-2.5 font-medium text-gray-600 hover:bg-gray-50 sm:inline-flex">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" wire:click.prevent="nextPage" rel="next" aria-label="{{ __('pagination.next') }}" class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-gray-200 px-3 font-medium text-gray-600 hover:bg-gray-50">
                    Next <span aria-hidden="true">&rarr;</span>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="inline-flex min-h-9 items-center gap-1 rounded-lg border border-gray-200 px-3 text-gray-400">
                    Next <span aria-hidden="true">&rarr;</span>
                </span>
            @endif
        </div>
    </nav>
@endif
