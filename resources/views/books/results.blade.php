@extends('layouts.app')

@section('title', 'Resultados: ' . $query)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Search bar (repete para refinamento) --}}
    <div class="mb-8">
        <form method="GET" action="{{ route('books.results') }}" class="flex gap-2 max-w-2xl">
            <div class="flex flex-1 shadow-sm rounded-xl overflow-hidden border border-gray-200 bg-white">
                <input type="text"
                       name="q"
                       value="{{ $query }}"
                       placeholder="Refine sua busca..."
                       class="flex-1 px-4 py-2.5 text-sm outline-none text-gray-800">
                <button type="submit"
                        class="px-4 py-2.5 bg-amber hover:bg-amber-dark text-ink transition-colors text-sm font-medium">
                    Buscar
                </button>
            </div>
            <a href="{{ route('books.search') }}"
               class="px-4 py-2.5 border border-gray-200 text-gray-500 hover:text-gray-700 rounded-xl text-sm transition-colors bg-white">
                Limpar
            </a>
        </form>
    </div>

    {{-- Header com contagem --}}
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="font-serif text-2xl font-bold text-ink">
                Resultados para <span class="text-amber">"{{ $query }}"</span>
            </h1>
            @if ($total > 0)
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ number_format($total) }} livro(s) encontrado(s) · Página {{ $currentPage }} de {{ $pages }}
                </p>
            @endif
        </div>
    </div>

    {{-- Results grid --}}
    @if ($books->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-16 text-center">
            <div class="text-6xl mb-4">😔</div>
            <h2 class="font-serif text-xl font-semibold text-gray-700 mb-2">Nenhum livro encontrado</h2>
            <p class="text-gray-400 mb-6">Tente outros termos ou verifique a ortografia.</p>
            <a href="{{ route('books.search') }}"
               class="inline-block px-6 py-2.5 bg-amber hover:bg-amber-dark text-ink font-semibold rounded-lg transition-colors">
                ← Voltar à pesquisa
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 mb-10">
            @foreach ($books as $book)
                <x-book-card
                    :book="$book"
                    :saved="in_array($book['open_library_id'], $savedIds)"
                    :query="$query"
                />
            @endforeach
        </div>

        {{-- Paginação manual (API não retorna paginação Eloquent) --}}
        @if ($pages > 1)
            <div class="flex items-center justify-center gap-2 flex-wrap">
                {{-- Anterior --}}
                @if ($currentPage > 1)
                    <a href="{{ route('books.results', ['q' => $query, 'page' => $currentPage - 1]) }}"
                       class="px-4 py-2 text-sm rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-amber hover:text-amber transition-colors">
                        ← Anterior
                    </a>
                @endif

                {{-- Páginas numéricas --}}
                @php
                    $start = max(1, $currentPage - 2);
                    $end   = min($pages, $currentPage + 2);
                @endphp

                @if ($start > 1)
                    <a href="{{ route('books.results', ['q' => $query, 'page' => 1]) }}"
                       class="px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-amber hover:text-amber transition-colors">1</a>
                    @if ($start > 2) <span class="text-gray-400 px-1">…</span> @endif
                @endif

                @for ($p = $start; $p <= $end; $p++)
                    <a href="{{ route('books.results', ['q' => $query, 'page' => $p]) }}"
                       class="px-3 py-2 text-sm rounded-lg border transition-colors
                              {{ $p === $currentPage
                                  ? 'bg-amber border-amber text-ink font-semibold'
                                  : 'border-gray-200 bg-white text-gray-600 hover:border-amber hover:text-amber' }}">
                        {{ $p }}
                    </a>
                @endfor

                @if ($end < $pages)
                    @if ($end < $pages - 1) <span class="text-gray-400 px-1">…</span> @endif
                    <a href="{{ route('books.results', ['q' => $query, 'page' => $pages]) }}"
                       class="px-3 py-2 text-sm rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-amber hover:text-amber transition-colors">{{ $pages }}</a>
                @endif

                {{-- Próxima --}}
                @if ($currentPage < $pages)
                    <a href="{{ route('books.results', ['q' => $query, 'page' => $currentPage + 1]) }}"
                       class="px-4 py-2 text-sm rounded-lg border border-gray-200 bg-white text-gray-600 hover:border-amber hover:text-amber transition-colors">
                        Próxima →
                    </a>
                @endif
            </div>
        @endif
    @endif
</div>
@endsection
