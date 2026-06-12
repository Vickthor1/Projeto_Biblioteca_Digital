{{--
    Componente: book-card
    Props:
        $book    - array com dados do livro
        $saved   - bool: se já está nos favoritos do usuário
        $query   - string: termo de pesquisa atual (para paginação)
        $showRemove - bool: exibir botão de remover (na biblioteca pessoal)
        $favoriteId - int: ID do registro na tabela (para remover)
--}}
@props([
    'book'       => [],
    'saved'      => false,
    'query'      => '',
    'showRemove' => false,
    'favoriteId' => null,
])

<div class="book-card bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col">

    {{-- Capa do livro --}}
    <div class="relative bg-gray-100 h-52 flex items-center justify-center overflow-hidden">
        @if (!empty($book['cover_url']) && is_string($book['cover_url']))
            <img src="{{ htmlspecialchars($book['cover_url'], ENT_QUOTES, 'UTF-8') }}"
                 alt="Capa de {{ htmlspecialchars($book['title'] ?? 'Livro', ENT_QUOTES, 'UTF-8') }}"
                 class="w-full h-full object-cover"
                 loading="lazy"
                 onerror="this.onerror=null;this.src='{{ asset('images/no-cover.svg') }}'">
        @else
            {{-- Placeholder SVG quando não há capa --}}
            <div class="flex flex-col items-center justify-center text-gray-400 p-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-xs text-center font-medium">Sem capa disponível</p>
            </div>
        @endif

        {{-- Badge "Salvo" --}}
        @if ($saved && !$showRemove)
            <span class="absolute top-2 right-2 bg-forest text-white text-xs px-2 py-0.5 rounded-full font-medium shadow">
                ✓ Salvo
            </span>
        @endif
    </div>

    {{-- Informações do livro --}}
    <div class="flex-1 p-4 flex flex-col gap-1">
        <h3 class="font-serif font-semibold text-ink text-base leading-snug line-clamp-2">
            {{ htmlspecialchars($book['title'] ?? 'Título desconhecido', ENT_QUOTES, 'UTF-8') }}
        </h3>

        @if (!empty($book['author']) && is_string($book['author']))
            <p class="text-sm text-gray-600 line-clamp-1">
                <span class="text-gray-400">por</span> {{ htmlspecialchars($book['author'], ENT_QUOTES, 'UTF-8') }}
            </p>
        @endif

        <div class="flex flex-wrap gap-2 mt-1">
            @if (!empty($book['publication_year']) && is_numeric($book['publication_year']))
                <span class="inline-flex items-center text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                    📅 {{ intval($book['publication_year']) }}
                </span>
            @endif
            @if (!empty($book['isbn']) && is_string($book['isbn']))
                <span class="inline-flex items-center text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded font-mono">
                    ISBN {{ htmlspecialchars($book['isbn'], ENT_QUOTES, 'UTF-8') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Ações --}}
    <div class="px-4 pb-4">
        @if ($showRemove && $favoriteId)
            {{-- Botão Remover (na biblioteca pessoal) --}}
            <form method="POST" action="{{ route('favorites.destroy', $favoriteId) }}" class="delete-form" data-disable-on-submit="true">
                @csrf
                @method('DELETE')
                <input type="hidden" name="confirm" value="1">
                <button type="submit"
                        class="w-full py-2 px-4 text-sm font-medium rounded-lg border border-red-200 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white hover:border-red-600 transition-colors">
                    🗑️ Remover da biblioteca
                </button>
            </form>
        @elseif ($saved)
            <button disabled
                    class="w-full py-2 px-4 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                ✓ Já está na sua biblioteca
            </button>
        @else
            {{-- Botão Salvar (nos resultados de pesquisa) --}}
            <form method="POST" action="{{ route('favorites.store') }}" data-disable-on-submit="true">
                @csrf
                <input type="hidden" name="open_library_id"  value="{{ htmlspecialchars($book['open_library_id'] ?? '', ENT_QUOTES, 'UTF-8') }}">
                <input type="hidden" name="title"            value="{{ htmlspecialchars($book['title'] ?? '', ENT_QUOTES, 'UTF-8') }}">
                <input type="hidden" name="author"           value="{{ htmlspecialchars($book['author'] ?? '', ENT_QUOTES, 'UTF-8') }}">
                <input type="hidden" name="publication_year" value="{{ intval($book['publication_year'] ?? 0) ?: '' }}">
                <input type="hidden" name="isbn"             value="{{ htmlspecialchars($book['isbn'] ?? '', ENT_QUOTES, 'UTF-8') }}">
                <input type="hidden" name="cover_url"        value="{{ htmlspecialchars($book['cover_url'] ?? '', ENT_QUOTES, 'UTF-8') }}">
                <button type="submit"
                        class="w-full py-2 px-4 text-sm font-medium rounded-lg bg-amber text-ink hover:bg-amber-dark transition-colors">
                    ❤️ Salvar na biblioteca
                </button>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const bookTitle = this.closest('.book-card')?.querySelector('h3')?.textContent?.trim() || 'este livro';
            if (!confirm('Tem certeza que deseja remover "' + bookTitle + '" da sua biblioteca?')) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush

