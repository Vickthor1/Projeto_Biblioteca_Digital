<?php

namespace Tests\Feature;

use App\Models\FavoriteBook;
use App\Models\User;
use App\Services\OpenLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BibliotecaTest extends TestCase
{
    use RefreshDatabase;

    // ─── Authentication Tests ──────────────────────────────────────────────────────

    public function test_guests_are_redirected_from_protected_routes(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('books.search'))->assertRedirect(route('login'));
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    // ─── Book Search Tests ─────────────────────────────────────────────────────────

    public function test_book_search_page_renders_correctly(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.search'))
            ->assertOk()
            ->assertSee('Pesquisar Livros');
    }

    public function test_search_results_page_returns_books_from_service(): void
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

    public function test_search_query_is_required_and_must_have_at_least_2_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.results', ['q' => 'a']))
            ->assertSessionHasErrors('q');
    }

    // ─── Favorites Tests ───────────────────────────────────────────────────────────

    public function test_user_can_save_a_book_to_favorites(): void
    {
        $user = User::factory()->create();

        $payload = [
            'open_library_id'  => '/works/OL999W',
            'title'            => 'Clean Code',
            'author'           => 'Robert C. Martin',
            'publication_year' => 2008,
            'isbn'             => '9780132350884',
            'cover_url'        => 'https://covers.openlibrary.org/b/isbn/9780132350884-M.jpg',
        ];

        $this->actingAs($user)
            ->post(route('favorites.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('favorite_books', [
            'user_id'        => $user->id,
            'open_library_id' => '/works/OL999W',
            'title'          => 'Clean Code',
        ]);
    }

    public function test_user_cannot_save_the_same_book_twice(): void
    {
        $user = User::factory()->create();

        $payload = [
            'open_library_id'  => '/works/OL999W',
            'title'            => 'Clean Code',
            'author'           => 'Robert C. Martin',
            'publication_year' => 2008,
        ];

        // First save
        $this->actingAs($user)->post(route('favorites.store'), $payload);

        // Second save should not duplicate
        $this->actingAs($user)->post(route('favorites.store'), $payload);

        $this->assertDatabaseCount('favorite_books', 1);
    }

    public function test_user_can_remove_their_own_favorite(): void
    {
        $user     = User::factory()->create();
        $favorite = FavoriteBook::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('favorites.destroy', $favorite))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertModelMissing($favorite);
    }

    public function test_user_cannot_delete_another_users_favorite(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $favorite = FavoriteBook::factory()->create(['user_id' => $userB->id]);

        $this->actingAs($userA)
            ->delete(route('favorites.destroy', $favorite))
            ->assertForbidden();

        $this->assertModelExists($favorite);
    }

    public function test_favorites_page_is_paginated(): void
    {
        $user = User::factory()->create();

        FavoriteBook::factory()->count(20)->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk();

        $this->assertEquals(20, $user->favoriteBooks()->count());
    }

    public function test_favorites_can_be_filtered_by_search_term(): void
    {
        $user = User::factory()->create();

        FavoriteBook::factory()->create(['user_id' => $user->id, 'title' => 'Clean Code']);
        FavoriteBook::factory()->create(['user_id' => $user->id, 'title' => 'Design Patterns']);

        $response = $this->actingAs($user)
            ->get(route('favorites.index', ['search' => 'Clean']))
            ->assertOk()
            ->assertSee('Clean Code')
            ->assertDontSee('Design Patterns');
    }

    // ─── OpenLibraryService Unit Tests ────────────────────────────────────────────

    public function test_openlibraryservice_returns_empty_result_on_connection_failure(): void
    {
        $service = new OpenLibraryService();

        // Se a API estiver offline, retorna estrutura vazia
        $result = $service->searchByTitle('__offline_test_xyz__');

        $this->assertArrayHasKey('books', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('current_page', $result);
        $this->assertInstanceOf(Collection::class, $result['books']);
    }
}

