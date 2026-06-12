<?php

namespace App\Http\Controllers;

use App\Services\OpenLibraryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * BookController
 *
 * Responsável pela pesquisa de livros na Open Library API.
 * Delega toda a lógica de integração ao OpenLibraryService,
 * mantendo o controller enxuto conforme boas práticas.
 */
class BookController extends Controller
{
    public function __construct(
        private readonly OpenLibraryService $libraryService
    ) {}

    /**
     * Exibe a tela de pesquisa vazia.
     *
     * GET /books/search
     */
    public function search(): View
    {
        return view('books.search');
    }

    /**
     * Processa a pesquisa e exibe os resultados.
     *
     * GET /books/results?q=laravel&page=1
     */
    public function results(Request $request): View
    {
        $request->validate([
            'q'    => ['required', 'string', 'min:2', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = $request->string('q')->trim()->toString();
        $page  = (int) $request->query('page', 1);

        $result = $this->libraryService->searchByTitle($query, $page);

        // Coleta os IDs já favoritados pelo usuário para marcar nos cards
        $savedIds = [];

        if (auth()->check()) {
            $savedIds = auth()->user()
                ->favoriteBooks()
                ->pluck('open_library_id')
                ->toArray();
}

        return view('books.results', [
            'query'       => $query,
            'books'       => $result['books'],
            'total'       => $result['total'],
            'pages'       => $result['pages'],
            'currentPage' => $result['current_page'],
            'savedIds'    => $savedIds,
        ]);
    }
}
