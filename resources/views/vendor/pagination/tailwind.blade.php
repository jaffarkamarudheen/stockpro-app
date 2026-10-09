@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-3 w-full">
        <div class="flex justify-between flex-1 sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed rounded-lg">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex items-center px-3.5 py-1.5 text-xs font-semibold text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed rounded-lg">
                    {!! __('pagination.next') !!}
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs text-slate-500">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-bold text-slate-800">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-bold text-slate-800">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-bold text-slate-800">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </p>
            </div>

            <div>
                <span class="inline-flex items-center gap-1">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="px-2.5 py-1.5 text-xs text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="px-2.5 py-1.5 text-xs text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300 transition shadow-xs" aria-label="{{ __('pagination.previous') }}">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true" class="px-2.5 py-1.5 text-xs text-slate-400 font-semibold cursor-default">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 border border-indigo-600 rounded-lg shadow-xs cursor-default">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300 transition shadow-xs" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="px-2.5 py-1.5 text-xs text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300 transition shadow-xs" aria-label="{{ __('pagination.next') }}">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="px-2.5 py-1.5 text-xs text-slate-400 bg-slate-100 border border-slate-200 rounded-lg cursor-not-allowed">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
