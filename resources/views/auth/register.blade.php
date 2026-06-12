<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta · Biblioteca Digital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    ink: '#1a1a2e',
                    amber: { DEFAULT: '#e8a838', dark: '#c4891a' },
                }
            }}
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-ink to-gray-900 flex items-center justify-center px-4 py-10">

<div class="w-full max-w-md">

    {{-- Logo --}}
    <div class="text-center mb-8">
        <a href="{{ route('home') }}" class="inline-flex flex-col items-center gap-1">
            <span class="text-5xl">📚</span>
            <span class="font-serif text-2xl font-bold text-amber tracking-wide">Biblioteca Digital</span>
        </a>
        <p class="text-gray-400 mt-2 text-sm">Crie sua conta gratuita</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">

        @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}"
                       required autofocus autocomplete="name"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-amber focus:ring-2 focus:ring-amber/20 transition">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       required autocomplete="username"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-amber focus:ring-2 focus:ring-amber/20 transition">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                <input id="password" type="password" name="password"
                       required autocomplete="new-password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-amber focus:ring-2 focus:ring-amber/20 transition">
                <p class="text-xs text-gray-400 mt-1">Mínimo de 8 caracteres</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar senha</label>
                <input id="password_confirmation" type="password" name="password_confirmation"
                       required autocomplete="new-password"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm outline-none focus:border-amber focus:ring-2 focus:ring-amber/20 transition">
            </div>

            <button type="submit"
                    class="w-full py-3 bg-ink hover:bg-gray-800 text-white font-semibold rounded-lg transition-colors">
                Criar minha conta
            </button>
        </form>

        <p class="text-center text-sm text-gray-500 mt-6">
            Já tem conta?
            <a href="{{ route('login') }}" class="text-amber font-medium hover:underline">Entrar</a>
        </p>
    </div>
</div>

</body>
</html>
