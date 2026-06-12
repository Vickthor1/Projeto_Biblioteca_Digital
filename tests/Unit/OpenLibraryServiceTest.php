<?php

namespace Tests\Unit;

use App\Services\OpenLibraryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenLibraryServiceTest extends TestCase
{
    private OpenLibraryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OpenLibraryService();
        Cache::flush();
    }

    // ─── Cache Tests ───────────────────────────────────────────────────────────

    public function test_results_are_cached(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([
                'numFound' => 1,
                'docs'     => [[
                    'key'                 => '/works/OL123W',
                    'title'               => 'Laravel',
                    'author_name'         => ['John Doe'],
                    'first_publish_year'  => 2020,
                    'isbn'                => ['1234567890'],
                ]],
            ]),
        ]);

        $result1 = $this->service->searchByTitle('laravel', 1);
        Http::assertSentCount(1);
        
        $result2 = $this->service->searchByTitle('laravel', 1);
        $this->assertEquals($result1, $result2);
        Http::assertSentCount(1);
    }

    // ─── Valid Response Tests ──────────────────────────────────────────────────

    public function test_successfully_parses_valid_response(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([
                'numFound' => 2,
                'docs'     => [
                    [
                        'key'                 => '/works/OL1W',
                        'title'               => 'Book One',
                        'author_name'         => ['Author One'],
                        'first_publish_year'  => 2020,
                        'isbn'                => ['1111111111'],
                    ],
                    [
                        'key'                 => '/works/OL2W',
                        'title'               => 'Book Two',
                        'author_name'         => ['Author Two'],
                        'first_publish_year'  => 2021,
                        'isbn'                => ['2222222222'],
                    ],
                ],
            ]),
        ]);

        $result = $this->service->searchByTitle('test', 1);

        $this->assertArrayHasKey('books', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['books']);
    }

    public function test_handles_missing_author_field(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([
                'numFound' => 1,
                'docs'     => [[
                    'key'                 => '/works/OL123W',
                    'title'               => 'Book',
                    'first_publish_year'  => 2020,
                    'isbn'                => ['1234567890'],
                ]],
            ]),
        ]);

        $result = $this->service->searchByTitle('test', 1);
        $book = $result['books']->first();

        $this->assertNull($book['author']);
    }

    public function test_handles_missing_isbn(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([
                'numFound' => 1,
                'docs'     => [[
                    'key'                 => '/works/OL123W',
                    'title'               => 'Book',
                    'author_name'         => ['Author'],
                    'first_publish_year'  => 2020,
                ]],
            ]),
        ]);

        $result = $this->service->searchByTitle('test', 1);
        $book = $result['books']->first();

        $this->assertNull($book['isbn']);
        $this->assertNull($book['cover_url']);
    }

    public function test_paginates_results_correctly(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([
                'numFound' => 100,
                'docs'     => [],
            ]),
        ]);

        $result = $this->service->searchByTitle('test', 1);

        // 100 books / 12 per page = ~8.33 pages, so 9 pages total
        $this->assertEquals(9, $result['pages']);
    }

    // ─── Error Handling Tests ──────────────────────────────────────────────────

    public function test_handles_connection_failure(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $result = $this->service->searchByTitle('test', 1);

        $this->assertArrayHasKey('books', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['books']);
        $this->assertSame('Não foi possível conectar à Open Library. Tente novamente em alguns instantes.', $result['error']);
    }

    public function test_handles_timeout_as_connection_failure(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out');
        });

        $result = $this->service->searchByTitle('test', 1);

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['books']);
        $this->assertSame('Não foi possível conectar à Open Library. Tente novamente em alguns instantes.', $result['error']);
    }

    public function test_handles_non_200_response(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([], 500),
        ]);

        $result = $this->service->searchByTitle('test', 1);

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['books']);
        $this->assertSame('A Open Library está temporariamente indisponível. Por favor, tente novamente mais tarde.', $result['error']);
    }

    public function test_handles_invalid_json_response(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response('not json', 200),
        ]);

        $result = $this->service->searchByTitle('test', 1);

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['books']);
        $this->assertSame('A Open Library retornou dados inesperados. Tente novamente.', $result['error']);
    }

    public function test_sends_query_parameters_to_openlibrary_api(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'https://openlibrary.org/search.json'
                && $request['q'] === 'harry potter'
                && $request['limit'] === 12
                && $request['offset'] === 0) {
                return Http::response([
                    'numFound' => 1,
                    'docs'     => [[
                        'key'                => '/works/OL1W',
                        'title'              => 'Harry Potter',
                        'author_name'        => ['J. K. Rowling'],
                        'first_publish_year' => 1997,
                        'isbn'               => ['9780747532743'],
                    ]],
                ], 200);
            }

            return Http::response([], 500);
        });

        $result = $this->service->searchByTitle('harry potter', 1);

        $this->assertEquals(1, $result['total']);
        $this->assertCount(1, $result['books']);
        $this->assertSame('Harry Potter', $result['books']->first()['title']);
    }

    public function test_filters_out_books_without_title(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([
                'numFound' => 2,
                'docs'     => [
                    [
                        'key'   => '/works/OL1W',
                        'title' => 'Valid Book',
                    ],
                    [
                        'key' => '/works/OL2W',
                        // Missing title
                    ],
                ],
            ]),
        ]);

        $result = $this->service->searchByTitle('test', 1);

        $this->assertCount(1, $result['books']);
        $this->assertEquals('Valid Book', $result['books']->first()['title']);
    }

    // ─── Cover URL Tests ────────────────────────────────────────────────────────

    public function test_generates_correct_cover_url(): void
    {
        $url = $this->service->coverUrl('1234567890');

        $this->assertEquals('https://covers.openlibrary.org/b/isbn/1234567890-M.jpg', $url);
    }

    public function test_returns_null_for_blank_isbn(): void
    {
        $this->assertNull($this->service->coverUrl(''));
        $this->assertNull($this->service->coverUrl(null));
    }

    // ─── Edge Cases ────────────────────────────────────────────────────────────

    public function test_handles_empty_docs_array(): void
    {
        Http::fake([
            'https://openlibrary.org/search.json*' => Http::response([
                'numFound' => 0,
                'docs'     => [],
            ]),
        ]);

        $result = $this->service->searchByTitle('nonexistent', 1);

        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['books']);
        $this->assertEquals(0, $result['pages']);
    }
}
