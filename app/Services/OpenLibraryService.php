<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenLibraryService
 *
 * Centraliza todas as chamadas à Open Library API.
 * Aplica cache de 60 minutos para evitar requisições repetidas
 * e reduzir a latência percebida pelo usuário.
 */
class OpenLibraryService
{
    private const BASE_URL   = 'https://openlibrary.org';
    private const COVERS_URL = 'https://covers.openlibrary.org/b/isbn';
    private const CACHE_TTL  = 3600; // segundos
    private const PER_PAGE   = 12;

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * Pesquisa livros pelo título com paginação.
     *
     * @return array{books: Collection, total: int, pages: int, current_page: int}
     */
    public function searchByTitle(string $query, int $page = 1): array
    {
        $cacheKey = "openlibrary:search:{$query}:page:{$page}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($query, $page) {
            return $this->fetchSearch($query, $page);
        });
    }

    /**
     * Constrói a URL da capa a partir de um ISBN.
     */
    public function coverUrl(?string $isbn): ?string
    {
        if (blank($isbn)) {
            return null;
        }

        return self::COVERS_URL . "/{$isbn}-M.jpg";
    }

    // ─── Private Methods ──────────────────────────────────────────────────────

    /**
     * Executa a requisição HTTP e normaliza o retorno.
     */
    private function fetchSearch(string $query, int $page): array
    {
        $offset = ($page - 1) * self::PER_PAGE;

        try {
            $response = Http::timeout(10)
                ->get(self::BASE_URL . '/search.json', [
                    'q'      => $query,
                    'limit'  => self::PER_PAGE,
                    'offset' => $offset,
                    'fields' => 'key,title,author_name,first_publish_year,isbn,cover_i',
                ]);

            if ($response->failed()) {
                Log::warning('OpenLibrary API returned non-2xx response', [
                    'status' => $response->status(),
                    'query'  => $query,
                ]);
                return $this->emptyResult($page);
            }

            $data  = $response->json();
            $total = $data['numFound'] ?? 0;
            $docs  = $data['docs'] ?? [];

            $books = collect($docs)->map(fn (array $doc) => $this->normalize($doc));

            return [
                'books'        => $books,
                'total'        => $total,
                'pages'        => (int) ceil($total / self::PER_PAGE),
                'current_page' => $page,
            ];
        } catch (ConnectionException $e) {
            Log::error('OpenLibrary connection failed', ['message' => $e->getMessage()]);
            return $this->emptyResult($page);
        } catch (\Throwable $e) {
            Log::error('OpenLibrary unexpected error', ['message' => $e->getMessage()]);
            return $this->emptyResult($page);
        }
    }

    /**
     * Normaliza um documento bruto da API em um array padronizado.
     */
    private function normalize(array $doc): array
    {
        $isbn = $doc['isbn'][0] ?? null;

        return [
            'open_library_id'  => $doc['key'] ?? null,
            'title'            => $doc['title'] ?? 'Título desconhecido',
            'author'           => isset($doc['author_name'])
                ? implode(', ', array_slice($doc['author_name'], 0, 2))
                : null,
            'publication_year' => $doc['first_publish_year'] ?? null,
            'isbn'             => $isbn,
            'cover_url'        => $isbn ? $this->coverUrl($isbn) : null,
        ];
    }

    /**
     * Resultado vazio padronizado para uso em caso de erros.
     */
    private function emptyResult(int $page): array
    {
        return [
            'books'        => collect(),
            'total'        => 0,
            'pages'        => 0,
            'current_page' => $page,
        ];
    }
}
