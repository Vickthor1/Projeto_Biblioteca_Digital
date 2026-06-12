@extends('layouts.app')

@section('title', 'Bem-vindo')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-ink via-ink-light to-gray-900 flex flex-col items-center justify-center px-4 py-20 text-center">

    {{-- Hero --}}
    <div class="max-w-2xl">
        <div class="text-7xl mb-6 animate-bounce">📚</div>

        <h1 class="font-serif text-5xl sm:text-6xl font-bold text-white leading-tight mb-4">
            Sua biblioteca,<br>
            <span class="text-amber">do seu jeito</span>
        </h1>

        <p class="text-gray-300 text-lg mb-10 leading-relaxed">
            Pesquise entre milhões de livros da Open Library e crie
            sua coleção pessoal de leituras favoritas.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}"
               class="px-8 py-3 bg-amber hover:bg-amber-light text-ink font-semibold rounded-xl text-lg transition-colors shadow-lg">
                Começar agora — é grátis
            </a>
            <a href="{{ route('login') }}"
               class="px-8 py-3 border-2 border-white/20 hover:border-amber text-white hover:text-amber font-semibold rounded-xl text-lg transition-colors">
                Já tenho conta
            </a>
        </div>
    </div>

    {{-- Feature cards --}}
    <div class="mt-24 grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-4xl w-full">
        @foreach ([
            ['🔍', 'Pesquise livros', 'Acesse o acervo de milhões de obras da Open Library em segundos.'],
            ['❤️', 'Salve favoritos', 'Monte sua biblioteca pessoal com os livros que você ama.'],
            ['📱', 'Em qualquer lugar', 'Interface responsiva para desktop, tablet e smartphone.'],
        ] as $feature)
        <div class="bg-white/5 border border-white/10 rounded-2xl p-6 text-center text-white">
            <div class="text-4xl mb-3">{{ $feature[0] }}</div>
            <h3 class="font-semibold text-lg mb-1">{{ $feature[1] }}</h3>
            <p class="text-gray-400 text-sm">{{ $feature[2] }}</p>
        </div>
        @endforeach
    </div>
</div>
@endsection
