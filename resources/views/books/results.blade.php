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

    {{-- Error message --}}
    @if (!empty($error))
        <div class="bg-red-50 border border-red-200 rounded-2xl p-6 mb-8">
            <div class="flex items-start gap-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0v2m0-6v-2m0 0V7a2 2 0 012-2h.5a4.5 4.5 0 100 9h-.5a2 2 0 01-2-2zm0 0V5m0 16h4m0 0h1.5a2 2 0 002-2v-5.5A1.5 1.5 0 0020.5 12h-1m0 0H9m0 0h4m0 0V7a2 2 0 012-2h.5a4.5 4.5 0 100 9h-.5a2 2 0 01-2-2z"/>
                </svg>
                <div>
                    <h3 class="font-semibold text-red-900">Erro ao buscar livros</h3>
                    <p class="text-sm text-red-700 mt-1">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Results grid --}}
    @if (!empty($error) || $books->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-16 text-center">
            @if (!empty($error))
                <div class="text-6xl mb-4">⚠️</div>
                <h2 class="font-serif text-xl font-semibold text-gray-700 mb-2">Erro ao buscar livros</h2>
                <p class="text-gray-400 mb-6">{{ $error }}</p>
            @else
                <div class="text-6xl mb-4">😔</div>
                <h2 class="font-serif text-xl font-semibold text-gray-700 mb-2">Nenhum livro encontrado</h2>
                <p class="text-gray-400 mb-6">Tente outros termos ou verifique a ortografia.</p>
            @endif
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
