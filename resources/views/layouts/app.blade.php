<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Biblioteca Digital') · Biblioteca Digital</title>

    {{-- Tailwind CSS via CDN (substituir por Vite + npm em produção) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink:    { DEFAULT: '#1a1a2e', light: '#16213e' },
                        amber:  { DEFAULT: '#e8a838', light: '#f5c842', dark: '#c4891a' },
                        cream:  { DEFAULT: '#fdf6ec', dark: '#f5e6cc' },
                        forest: { DEFAULT: '#2d6a4f', light: '#40916c' },
                    },
                    fontFamily: {
                        serif: ['Georgia', 'Cambria', 'serif'],
                        sans:  ['Inter', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .book-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .book-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.12); }
    </style>

    @stack('styles')
</head>
<body class="h-full bg-cream flex flex-col">

    {{-- ── Navbar ──────────────────────────────────────────────────────── --}}
    <nav class="bg-ink shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <span class="text-2xl">📚</span>
                    <span class="text-amber font-serif font-bold text-lg tracking-wide group-hover:text-amber-light transition-colors">
                        Biblioteca Digital
                    </span>
                </a>

                {{-- Nav links (desktop) --}}
                @auth
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-ink-light transition-colors
                              {{ request()->routeIs('dashboard') ? 'text-white bg-ink-light' : '' }}">
                        Início
                    </a>
                    <a href="{{ route('books.search') }}"
                       class="nav-link px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-ink-light transition-colors
                              {{ request()->routeIs('books.*') ? 'text-white bg-ink-light' : '' }}">
                        🔍 Pesquisar
                    </a>
                    <a href="{{ route('favorites.index') }}"
                       class="nav-link px-3 py-2 rounded-md text-sm font-medium text-gray-300 hover:text-white hover:bg-ink-light transition-colors
                              {{ request()->routeIs('favorites.*') ? 'text-white bg-ink-light' : '' }}">
                        ❤️ Minha Biblioteca
                    </a>
                </div>

                {{-- User menu (desktop) --}}
                <div class="hidden md:flex items-center gap-4">
                    <span class="text-gray-400 text-sm">Olá, <span class="text-amber font-medium">{{ auth()->user()->name }}</span></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 text-sm rounded-md border border-gray-600 text-gray-300 hover:bg-red-900/30 hover:border-red-500 hover:text-red-300 transition-colors">
                            Sair
                        </button>
                    </form>
                </div>

                {{-- Mobile menu button --}}
                <button id="mobile-menu-btn" class="md:hidden text-gray-400 hover:text-white p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                @else
                <div class="flex items-center gap-2">
                    <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white transition-colors">Entrar</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-medium bg-amber hover:bg-amber-light text-ink rounded-md transition-colors">Criar conta</a>
                </div>
                @endauth
            </div>

            {{-- Mobile menu --}}
            @auth
            <div id="mobile-menu" class="hidden md:hidden pb-3 border-t border-gray-700 pt-3">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">Início</a>
                <a href="{{ route('books.search') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">🔍 Pesquisar</a>
                <a href="{{ route('favorites.index') }}" class="block px-3 py-2 text-sm text-gray-300 hover:text-white">❤️ Minha Biblioteca</a>
                <div class="mt-2 pt-2 border-t border-gray-700">
                    <span class="block px-3 py-1 text-xs text-gray-500">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="px-3 pt-1">
                        @csrf
                        <button type="submit" class="text-sm text-red-400 hover:text-red-300">Sair da conta</button>
                    </form>
                </div>
            </div>
            @endauth
        </div>
    </nav>

    {{-- ── Flash Messages ───────────────────────────────────────────────── --}}
    @if (session('success'))
        <div id="flash-success"
             class="fixed top-20 right-4 z-50 max-w-sm bg-forest text-white px-5 py-3 rounded-lg shadow-xl flex items-center gap-3 animate-pulse">
            <span class="text-xl">✅</span>
            <p class="text-sm font-medium">{{ session('success') }}</p>
            <button onclick="this.parentElement.remove()" class="ml-auto text-white/70 hover:text-white text-lg leading-none">×</button>
        </div>
    @endif
    @if (session('error'))
        <div id="flash-error"
             class="fixed top-20 right-4 z-50 max-w-sm bg-red-700 text-white px-5 py-3 rounded-lg shadow-xl flex items-center gap-3">
            <span class="text-xl">⚠️</span>
            <p class="text-sm font-medium">{{ session('error') }}</p>
            <button onclick="this.parentElement.remove()" class="ml-auto text-white/70 hover:text-white text-lg leading-none">×</button>
        </div>
    @endif

    {{-- ── Page Content ──────────────────────────────────────────────────── --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ── Footer ───────────────────────────────────────────────────────── --}}
    <footer class="bg-ink text-gray-500 text-center text-xs py-4 mt-12">
        <p>Biblioteca Digital &copy; {{ date('Y') }} · Powered by <a href="https://openlibrary.org" target="_blank" class="text-amber hover:underline">Open Library API</a></p>
    </footer>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn')?.addEventListener('click', () => {
            document.getElementById('mobile-menu')?.classList.toggle('hidden');
        });

        // Auto-hide flash messages after 4s
        setTimeout(() => {
            document.getElementById('flash-success')?.remove();
            document.getElementById('flash-error')?.remove();
        }, 4000);

        // Prevent duplicate form submissions when JavaScript is available
        document.addEventListener('submit', event => {
            const form = event.target.closest('form');
            if (!form?.dataset?.disableOnSubmit) {
                return;
            }

            const submitButton = form.querySelector('[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-50', 'cursor-not-allowed');
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
