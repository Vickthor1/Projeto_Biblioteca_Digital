@extends('layouts.app')

@section('title', 'Minha Biblioteca')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink">❤️ Minha Biblioteca</h1>
            <p class="text-gray-500 mt-1">
                {{ $favorites->total() }} livro(s) na coleção
            </p>
        </div>
        <a href="{{ route('books.search') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-amber hover:bg-amber-dark text-ink font-semibold rounded-xl transition-colors shadow-sm">
            🔍 Buscar mais livros
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('favorites.index') }}" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-8 flex flex-col sm:flex-row gap-3">
        {{-- Text search --}}
        <div class="flex-1 flex rounded-lg overflow-hidden border border-gray-200">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Buscar por título ou autor..."
                   class="flex-1 px-4 py-2.5 text-sm outline-none text-gray-800">
            <button type="submit"
                    class="px-4 py-2 bg-gray-50 border-l border-gray-200 text-gray-500 hover:text-amber transition-colors text-sm">
                🔍
            </button>
        </div>

        {{-- Author filter --}}
        @if ($authors->isNotEmpty())
            <select name="author"
                    onchange="this.form.submit()"
                    class="px-4 py-2.5 text-sm border border-gray-200 rounded-lg bg-white text-gray-700 outline-none focus:border-amber">
                <option value="">Todos os autores</option>
                @foreach ($authors as $author)
                    <option value="{{ $author }}" {{ request('author') === $author ? 'selected' : '' }}>
                        {{ Str::limit($author, 30) }}
                    </option>
                @endforeach
            </select>
        @endif

        {{-- Clear filters --}}
        @if (request('search') || request('author'))
            <a href="{{ route('favorites.index') }}"
               class="px-4 py-2.5 text-sm border border-gray-200 rounded-lg text-gray-500 hover:text-red-500 hover:border-red-200 transition-colors bg-white">
                ✕ Limpar
            </a>
        @endif
    </form>

    {{-- Empty state --}}
    @if ($favorites->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-16 text-center">
            @if (request('search') || request('author'))
                <div class="text-6xl mb-4">🔎</div>
                <h2 class="font-serif text-xl font-semibold text-gray-700 mb-2">Nenhum livro encontrado</h2>
                <p class="text-gray-400 mb-6">Nenhum resultado para os filtros aplicados.</p>
                <a href="{{ route('favorites.index') }}"
                   class="inline-block px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors">
                    ← Limpar filtros
                </a>
            @else
                <div class="text-6xl mb-4">📭</div>
                <h2 class="font-serif text-xl font-semibold text-gray-700 mb-2">Sua biblioteca está vazia</h2>
                <p class="text-gray-400 mb-6">Pesquise livros e salve os que você ama aqui.</p>
                <a href="{{ route('books.search') }}"
                   class="inline-block px-6 py-2.5 bg-amber hover:bg-amber-dark text-ink font-semibold rounded-lg transition-colors">
                    🔍 Pesquisar livros
                </a>
            @endif
        </div>
    @else
        {{-- Books grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 mb-8">
            @foreach ($favorites as $favorite)
                <x-book-card
                    :book="$favorite->toArray()"
                    :saved="true"
                    :showRemove="true"
                    :favoriteId="$favorite->id"
                />
            @endforeach
        </div>

        {{-- Paginação Eloquent --}}
        @if ($favorites->hasPages())
            <div class="flex justify-center">
                {{ $favorites->withQueryString()->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    @endif
</div>
@endsection
