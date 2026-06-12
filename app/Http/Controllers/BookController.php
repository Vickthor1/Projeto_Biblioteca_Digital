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
            'q'    => ['required', 'string', 'min:2', 'max:100', 'regex:/\S+/'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $query = trim((string) $request->query('q', ''));
        $page  = (int) $request->query('page', 1);

        \Illuminate\Support\Facades\Log::info('Book search requested', [
            'query' => $query,
            'page'  => $page,
            'url'   => $request->fullUrl(),
        ]);

        try {
            $result = $this->libraryService->searchByTitle($query, $page);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Book search failed', [
                'query'   => $query,
                'page'    => $page,
                'message' => $e->getMessage(),
            ]);

            return view('books.results', [
                'query'       => $query,
                'books'       => collect(),
                'total'       => 0,
                'pages'       => 0,
                'currentPage' => $page,
                'savedIds'    => [],
                'error'       => 'Não conseguimos buscar livros no momento. Tente novamente em alguns instantes.',
            ]);
        }

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
            'error'       => $result['error'] ?? null,
        ]);
    }
}
