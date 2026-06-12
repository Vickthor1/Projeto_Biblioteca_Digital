@extends('layouts.app')

@section('title', 'Pesquisar Livros')

@section('content')
<div class="min-h-[70vh] flex flex-col items-center justify-center px-4 py-16">

    {{-- Header --}}
    <div class="text-center mb-10 max-w-xl">
        <div class="text-5xl mb-4">🔍</div>
        <h1 class="font-serif text-4xl font-bold text-ink mb-3">Pesquisar Livros</h1>
        <p class="text-gray-500">
            Busque no acervo da <a href="https://openlibrary.org" target="_blank" class="text-amber hover:underline">Open Library</a>
            com milhões de títulos disponíveis.
        </p>
    </div>

    {{-- Search Form --}}
    <form method="GET" action="{{ route('books.results') }}" class="w-full max-w-2xl" data-disable-on-submit="true">
        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                {{ $errors->first('q') }}
            </div>
        @endif

        <div class="flex gap-2 shadow-xl rounded-2xl overflow-hidden border border-gray-200 bg-white">
            <input type="text"
                   name="q"
                   id="search-input"
                   value="{{ old('q') }}"
                   placeholder="Digite o título do livro..."
                   autofocus
                   required
                   minlength="2"
                   maxlength="100"
                   class="flex-1 px-6 py-4 text-base outline-none text-gray-800 placeholder-gray-400">
            <button type="submit"
                    class="px-6 py-4 bg-amber hover:bg-amber-dark text-ink font-semibold transition-colors flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Buscar
            </button>
        </div>

        <p class="text-center text-xs text-gray-400 mt-3">Pressione Enter ou clique em Buscar</p>
    </form>

    {{-- Suggestions --}}
    <div class="mt-10 text-center">
        <p class="text-sm text-gray-400 mb-3">Não sabe o que buscar? Experimente:</p>
        <div class="flex flex-wrap gap-2 justify-center">
            @foreach (['Dom Quixote', 'Harry Potter', 'O Hobbit', 'Dune', '1984', 'Clean Code'] as $suggestion)
                <a href="{{ route('books.results', ['q' => $suggestion]) }}"
                   class="px-3 py-1.5 text-xs bg-white border border-gray-200 text-gray-600 hover:border-amber hover:text-amber rounded-full transition-colors shadow-sm">
                    {{ $suggestion }}
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
