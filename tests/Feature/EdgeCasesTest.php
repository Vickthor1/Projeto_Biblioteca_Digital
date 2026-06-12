<?php

namespace Tests\Feature;

use App\Models\FavoriteBook;
use App\Models\User;
use App\Services\OpenLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    // ─── Validação de Entrada ─────────────────────────────────────────────────────

    public function test_search_rejects_empty_query(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('books.results', ['q' => '']))
            ->assertSessionHasErrors('q');
    }

    public function test_search_rejects_query_with_only_spaces(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('books.results', ['q' => '   ']))
            ->assertSessionHasErrors('q');
    }

    public function test_search_rejects_single_character(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'a']))
            ->assertSessionHasErrors('q');
    }

    public function test_search_rejects_query_exceeding_max_length(): void
    {
        $user = User::factory()->create();
        $longQuery = str_repeat('a', 101);
        $this->actingAs($user)
            ->get(route('books.results', ['q' => $longQuery]))
            ->assertSessionHasErrors('q');
    }

    // ─── Paginação ────────────────────────────────────────────────────────────────

    public function test_invalid_page_number_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'laravel', 'page' => 'invalid']))
            ->assertSessionHasErrors('page');
    }

    public function test_zero_page_number_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'laravel', 'page' => 0]))
            ->assertSessionHasErrors('page');
    }

    // ─── Livros com Dados Incompletos ─────────────────────────────────────────────

    public function test_book_without_cover_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->mock(OpenLibraryService::class)
            ->shouldReceive('searchByTitle')
            ->andReturn([
                'books' => collect([[
                    'open_library_id'  => '/works/OL123W',
                    'title'            => 'Book Without Cover',
                    'author'           => 'Author',
                    'publication_year' => 2020,
                    'isbn'             => null,
                    'cover_url'        => null,
                ]]),
                'total'        => 1,
                'pages'        => 1,
                'current_page' => 1,
            ]);

        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'test']))
            ->assertOk()
            ->assertSee('Book Without Cover');
    }

    public function test_book_without_author_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->mock(OpenLibraryService::class)
            ->shouldReceive('searchByTitle')
            ->andReturn([
                'books' => collect([[
                    'open_library_id'  => '/works/OL123W',
                    'title'            => 'Some Book',
                    'author'           => null,
                    'publication_year' => 2020,
                    'isbn'             => null,
                    'cover_url'        => null,
                ]]),
                'total'        => 1,
                'pages'        => 1,
                'current_page' => 1,
            ]);

        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'test']))
            ->assertOk()
            ->assertSee('Some Book');
    }

    // ─── Dashboard Edge Cases ──────────────────────────────────────────────────────

    public function test_dashboard_displays_correctly_with_no_favorites(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sua biblioteca está vazia');
    }

    public function test_dashboard_displays_with_many_favorites(): void
    {
        $user = User::factory()->create();
        FavoriteBook::factory()->count(100)->create(['user_id' => $user->id]);
        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Adicionados recentemente');
    }

    // ─── Validação de Dados na Requisição ──────────────────────────────────────────

    public function test_cannot_save_favorite_without_open_library_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('favorites.store'), ['title' => 'Test Book'])
            ->assertSessionHasErrors('open_library_id');
    }

    public function test_cannot_save_favorite_without_title(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('favorites.store'), ['open_library_id' => '/works/OL123W'])
            ->assertSessionHasErrors('title');
    }

    public function test_cannot_save_favorite_with_invalid_open_library_id_format(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('favorites.store'), [
                'open_library_id' => 'invalid-format',
                'title'           => 'Test Book',
            ])
            ->assertSessionHasErrors('open_library_id');
    }

    public function test_cannot_save_favorite_with_invalid_isbn_format(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('favorites.store'), [
                'open_library_id' => '/works/OL123W',
                'title'           => 'Test Book',
                'isbn'            => 'invalid@isbn',
            ])
            ->assertSessionHasErrors('isbn');
    }

    public function test_cover_url_must_use_https_protocol(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->post(route('favorites.store'), [
                'open_library_id' => '/works/OL123W',
                'title'           => 'Test Book',
                'cover_url'       => 'http://example.com/cover.jpg',
            ])
            ->assertSessionHasErrors('cover_url');
    }

    // ─── Acesso Desautorizado ─────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_search(): void
    {
        $this->get(route('books.search'))->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_cannot_save_favorites(): void
    {
        $this->post(route('favorites.store'), [
            'open_library_id' => '/works/OL123W',
            'title'           => 'Test Book',
        ])->assertRedirect(route('login'));
    }

    public function test_unauthenticated_user_cannot_view_favorites(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
    }
}
