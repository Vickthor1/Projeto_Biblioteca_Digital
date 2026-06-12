@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="font-serif text-3xl font-bold text-ink">
            Olá, {{ auth()->user()->name }} 👋
        </h1>
        <p class="text-gray-500 mt-1">O que vamos ler hoje?</p>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-10">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
            <div class="bg-amber/10 rounded-full p-3 text-3xl">📖</div>
            <div>
                <p class="text-3xl font-bold text-ink">{{ $totalFavorites }}</p>
                <p class="text-sm text-gray-500">livro(s) na biblioteca</p>
            </div>
        </div>
        <a href="{{ route('books.search') }}"
           class="bg-ink rounded-2xl shadow-sm p-6 flex items-center gap-4 hover:bg-ink-light transition-colors group">
            <div class="bg-amber/10 rounded-full p-3 text-3xl">🔍</div>
            <div>
                <p class="text-lg font-bold text-amber group-hover:text-amber-light">Pesquisar livros</p>
                <p class="text-sm text-gray-400">Open Library · milhões de títulos</p>
            </div>
        </a>
        <a href="{{ route('favorites.index') }}"
           class="bg-forest rounded-2xl shadow-sm p-6 flex items-center gap-4 hover:bg-forest-light transition-colors group">
            <div class="bg-white/10 rounded-full p-3 text-3xl">❤️</div>
            <div>
                <p class="text-lg font-bold text-white">Minha biblioteca</p>
                <p class="text-sm text-white/60">Ver todos os favoritos</p>
            </div>
        </a>
    </div>

    {{-- Recent Favorites --}}
    <div>
        <div class="flex items-center justify-between mb-5">
            <h2 class="font-serif text-xl font-bold text-ink">Adicionados recentemente</h2>
            @if ($totalFavorites > 0)
                <a href="{{ route('favorites.index') }}" class="text-sm text-amber hover:underline">Ver todos →</a>
            @endif
        </div>

        @if ($recentFavorites->isEmpty())
            {{-- Estado vazio --}}
            <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-14 text-center">
                <div class="text-6xl mb-4">📭</div>
                <h3 class="font-serif text-xl font-semibold text-gray-700 mb-2">Sua biblioteca está vazia</h3>
                <p class="text-gray-400 mb-6">Pesquise livros e salve os que você ama aqui.</p>
                <a href="{{ route('books.search') }}"
                   class="inline-block px-6 py-2.5 bg-amber hover:bg-amber-dark text-ink font-semibold rounded-lg transition-colors">
                    🔍 Pesquisar livros
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ($recentFavorites as $favorite)
                    <x-book-card
                        :book="$favorite->toArray()"
                        :saved="true"
                        :showRemove="true"
                        :favoriteId="$favorite->id"
                    />
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
