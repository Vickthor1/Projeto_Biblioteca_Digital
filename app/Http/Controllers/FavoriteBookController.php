<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFavoriteBookRequest;
use App\Models\FavoriteBook;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FavoriteBookController
 *
 * Gerencia a biblioteca pessoal do usuário autenticado.
 * Todas as ações verificam automaticamente a propriedade
 * do recurso via FavoriteBookPolicy.
 */
class FavoriteBookController extends Controller
{
    /**
     * Lista os livros favoritos do usuário autenticado.
     * Suporta pesquisa por título/autor e filtro por autor.
     *
     * GET /favorites
     */
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'author' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $query = auth()->user()
            ->favoriteBooks()
            ->latest();

        // Filtro de texto livre (título ou autor)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        // Filtro por autor exato (via select)
        if ($author = $request->query('author')) {
            $query->where('author', $author);
        }

        $favorites = $query->paginate(12)->withQueryString();

        // Autores únicos para o filtro
        $authors = auth()->user()
            ->favoriteBooks()
            ->whereNotNull('author')
            ->orderBy('author')
            ->pluck('author')
            ->unique()
            ->values();

        return view('favorites.index', compact('favorites', 'authors'));
    }

    /**
     * Salva um livro na biblioteca pessoal do usuário.
     *
     * POST /favorites
     */
    public function store(StoreFavoriteBookRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Tenta criar; ignora silenciosamente se já existir (unique constraint)
        FavoriteBook::firstOrCreate(
            [
                'user_id'         => auth()->id(),
                'open_library_id' => $data['open_library_id'],
            ],
            [
                'title'            => $data['title'],
                'author'           => $data['author'] ?? null,
                'publication_year' => $data['publication_year'] ?? null,
                'isbn'             => $data['isbn'] ?? null,
                'cover_url'        => $data['cover_url'] ?? null,
            ]
        );

        return back()->with('success', ""{$data['title']}" foi adicionado à sua biblioteca.");
    }

    /**
     * Remove um livro da biblioteca pessoal do usuário.
     *
     * DELETE /favorites/{favorite}
     */
    public function destroy(FavoriteBook $favorite): RedirectResponse
    {
        $this->authorize('delete', $favorite);

        $title = $favorite->title;
        $favorite->delete();

        return back()->with('success', ""{$title}" foi removido da sua biblioteca.");
    }
}
