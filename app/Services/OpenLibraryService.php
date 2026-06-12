<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
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

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return $this->buildResult(
                $cached['books'] ?? [],
                (int) ($cached['total'] ?? 0),
                (int) ($cached['pages'] ?? 0),
                (int) ($cached['current_page'] ?? $page),
                $cached['error'] ?? null
            );
        }

        $result = $this->fetchSearch($query, $page);

        if (empty($result['error'])) {
            Cache::put($cacheKey, [
                'books'        => $result['books']->all(),
                'total'        => $result['total'],
                'pages'        => $result['pages'],
                'current_page' => $result['current_page'],
                'error'        => $result['error'] ?? null,
            ], self::CACHE_TTL);
        }

        return $result;
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
        $queryParams = [
            'q'      => $query,
            'limit'  => self::PER_PAGE,
            'offset' => $offset,
            'fields' => 'key,title,author_name,first_publish_year,isbn,cover_i',
        ];

        try {
            $response = $this->sendSearchRequest($queryParams);
            return $this->processSearchResponse($response, $query, $page, $queryParams, true);
        } catch (ConnectionException $e) {
            if ($this->isSslVerificationError($e)) {
                Log::warning('OpenLibrary SSL verification failed; retrying without verify', [
                    'query'   => $query,
                    'page'    => $page,
                    'message' => $e->getMessage(),
                    'url'     => self::BASE_URL . '/search.json',
                    'params'  => $queryParams,
                ]);

                try {
                    $response = $this->sendSearchRequest($queryParams, false);
                    return $this->processSearchResponse($response, $query, $page, $queryParams, false);
                } catch (ConnectionException $retryException) {
                    Log::error('OpenLibrary retry without SSL verification failed', [
                        'query'   => $query,
                        'page'    => $page,
                        'message' => $retryException->getMessage(),
                        'url'     => self::BASE_URL . '/search.json',
                        'params'  => $queryParams,
                    ]);
                }
            }

            Log::error('OpenLibrary connection failed', [
                'query'   => $query,
                'page'    => $page,
                'message' => $e->getMessage(),
                'url'     => self::BASE_URL . '/search.json',
                'params'  => $queryParams,
            ]);

            return $this->buildResult([], 0, 0, $page, 'Não foi possível conectar à Open Library. Tente novamente em alguns instantes.');
        } catch (\Throwable $e) {
            Log::error('OpenLibrary unexpected error', [
                'query'   => $query,
                'page'    => $page,
                'message' => $e->getMessage(),
                'class'   => get_class($e),
            ]);

            return $this->buildResult([], 0, 0, $page, 'Ocorreu um erro inesperado ao buscar livros. Tente novamente.');
        }
    }

    private function sendSearchRequest(array $queryParams, bool $verify = true): Response
    {
        return Http::withOptions(['verify' => $verify])
            ->timeout(10)
            ->get(self::BASE_URL . '/search.json', $queryParams);
    }

    private function isSslVerificationError(ConnectionException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'ssl peer certificate')
            || str_contains($message, 'curl error 60')
            || str_contains($message, 'ssl certificate')
            || str_contains($message, 'certificate problem');
    }

    private function processSearchResponse(Response $response, string $query, int $page, array $queryParams, bool $verifySsl): array
    {
        if ($response->failed()) {
            Log::warning('OpenLibrary API returned non-2xx response', [
                'query'      => $query,
                'page'       => $page,
                'status'     => $response->status(),
                'url'        => self::BASE_URL . '/search.json',
                'params'     => $queryParams,
                'response'   => $response->body(),
                'verify_ssl' => $verifySsl,
            ]);

            return $this->buildResult([], 0, 0, $page, 'A Open Library está temporariamente indisponível. Por favor, tente novamente mais tarde.');
        }

        $data = $response->json();

        if (!is_array($data)) {
            Log::warning('OpenLibrary returned invalid JSON format', [
                'query'      => $query,
                'page'       => $page,
                'url'         => self::BASE_URL . '/search.json',
                'params'      => $queryParams,
                'body'        => $response->body(),
                'verify_ssl'  => $verifySsl,
            ]);

            return $this->buildResult([], 0, 0, $page, 'A Open Library retornou dados inesperados. Tente novamente.');
        }

        $total = (int) ($data['numFound'] ?? 0);
        $docs  = $data['docs'] ?? [];

        if (!is_array($docs)) {
            Log::warning('OpenLibrary docs field is not an array', [
                'query'      => $query,
                'page'       => $page,
                'url'         => self::BASE_URL . '/search.json',
                'params'      => $queryParams,
                'docs_type'   => gettype($docs),
                'verify_ssl'  => $verifySsl,
            ]);

            return $this->buildResult([], 0, 0, $page, 'A Open Library retornou dados inesperados. Tente novamente.');
        }

        $books = collect($docs)
            ->filter(fn ($doc) => is_array($doc) && !empty($doc['title']))
            ->map(fn (array $doc) => $this->normalize($doc));

        Log::info('OpenLibrary search response', [
            'query'      => $query,
            'page'       => $page,
            'status'     => $response->status(),
            'numFound'   => $total,
            'docs_count' => $books->count(),
            'verify_ssl' => $verifySsl,
        ]);

        return $this->buildResult($books, $total, (int) ceil($total / self::PER_PAGE), $page);
    }

    /**
     * Normaliza um documento bruto da API em um array padronizado.
     */
    private function normalize(array $doc): array
    {
        $isbn = null;
        if (isset($doc['isbn']) && is_array($doc['isbn']) && isset($doc['isbn'][0])) {
            $isbn = trim((string) $doc['isbn'][0]);
        }

        $authors = null;
        if (!empty($doc['author_name']) && is_array($doc['author_name'])) {
            $authors = array_filter($doc['author_name'], fn ($value) => is_string($value) && trim($value) !== '');
            $authors = $authors ? implode(', ', array_slice($authors, 0, 2)) : null;
        }

        $title = trim((string) ($doc['title'] ?? ''));

        return [
            'open_library_id'  => trim((string) ($doc['key'] ?? '')) ?: null,
            'title'            => $title !== '' ? $title : 'Título desconhecido',
            'author'           => $authors,
            'publication_year' => isset($doc['first_publish_year']) ? (int) $doc['first_publish_year'] : null,
            'isbn'             => $isbn !== '' ? $isbn : null,
            'cover_url'        => $isbn ? $this->coverUrl($isbn) : null,
        ];
    }

    /**
     * Resultado vazio padronizado para uso em caso de erros.
     */
    private function buildResult($books, int $total, int $pages, int $currentPage, ?string $error = null): array
    {
        if (! $books instanceof Collection) {
            $books = collect($books);
        }

        return [
            'books'        => $books,
            'total'        => $total,
            'pages'        => max(0, $pages),
            'current_page' => $currentPage,
            'error'        => $error,
        ];
    }
}

