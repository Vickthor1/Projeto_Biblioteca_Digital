<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OpenLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_and_results_route_are_accessible_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.search'))
            ->assertOk()
            ->assertSee('Pesquisar Livros');

        $this->mock(OpenLibraryService::class)
            ->shouldReceive('searchByTitle')
            ->once()
            ->with('laravel', 1)
            ->andReturn([
                'books'        => collect(),
                'total'        => 0,
                'pages'        => 0,
                'current_page' => 1,
            ]);

        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'laravel']))
            ->assertOk();
    }

    public function test_valid_search_returns_books(): void
    {
        $user = User::factory()->create();

        $fakeResult = [
            'books' => collect([[
                'open_library_id'  => '/works/OL123W',
                'title'            => 'Laravel for Beginners',
                'author'           => 'John Doe',
                'publication_year' => 2023,
                'isbn'             => '1234567890',
                'cover_url'        => 'https://example.com/cover.jpg',
            ]]),
            'total'        => 1,
            'pages'        => 1,
            'current_page' => 1,
        ];

        $this->mock(OpenLibraryService::class)
            ->shouldReceive('searchByTitle')
            ->once()
            ->with('laravel', 1)
            ->andReturn($fakeResult);

        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'laravel']))
            ->assertOk()
            ->assertSee('Laravel for Beginners')
            ->assertSee('John Doe');
    }

    public function test_search_nonexistent_returns_empty_state(): void
    {
        $user = User::factory()->create();

        $this->mock(OpenLibraryService::class)
            ->shouldReceive('searchByTitle')
            ->once()
            ->with('unknown book', 1)
            ->andReturn([
                'books'        => collect(),
                'total'        => 0,
                'pages'        => 0,
                'current_page' => 1,
            ]);

        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'unknown book']))
            ->assertOk()
            ->assertSee('Nenhum livro encontrado');
    }

    public function test_search_empty_query_fails_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.results', ['q' => '']))
            ->assertSessionHasErrors('q');
    }

    public function test_search_form_is_protected_against_multiple_submissions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.search'))
            ->assertOk()
            ->assertSee('data-disable-on-submit="true"');
    }
}
