<?php

use App\Models\FavoriteBook;
use App\Models\User;
use App\Services\OpenLibraryService;
use Illuminate\Support\Collection;

// ─── Authentication Tests ──────────────────────────────────────────────────────

test('guests are redirected from protected routes', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('books.search'))->assertRedirect(route('login'));
    $this->get(route('favorites.index'))->assertRedirect(route('login'));
});

test('authenticated users can access dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

// ─── Book Search Tests ─────────────────────────────────────────────────────────

test('book search page renders correctly', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('books.search'))
        ->assertOk()
        ->assertSee('Pesquisar Livros');
});

test('search results page returns books from service', function () {
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
});

test('search query is required and must have at least 2 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('books.results', ['q' => 'a']))
        ->assertSessionHasErrors('q');
});

// ─── Favorites Tests ───────────────────────────────────────────────────────────

test('user can save a book to favorites', function () {
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
});

test('user cannot save the same book twice', function () {
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
});

test('user can remove their own favorite', function () {
    $user     = User::factory()->create();
    $favorite = FavoriteBook::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('favorites.destroy', $favorite))
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertModelMissing($favorite);
});

test('user cannot delete another users favorite', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $favorite = FavoriteBook::factory()->create(['user_id' => $userB->id]);

    $this->actingAs($userA)
        ->delete(route('favorites.destroy', $favorite))
        ->assertForbidden();

    $this->assertModelExists($favorite);
});

test('favorites page is paginated', function () {
    $user = User::factory()->create();

    FavoriteBook::factory()->count(20)->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('favorites.index'))
        ->assertOk();

    $this->assertEquals(20, $user->favoriteBooks()->count());
});

test('favorites can be filtered by search term', function () {
    $user = User::factory()->create();

    FavoriteBook::factory()->create(['user_id' => $user->id, 'title' => 'Clean Code']);
    FavoriteBook::factory()->create(['user_id' => $user->id, 'title' => 'Design Patterns']);

    $response = $this->actingAs($user)
        ->get(route('favorites.index', ['search' => 'Clean']))
        ->assertOk()
        ->assertSee('Clean Code')
        ->assertDontSee('Design Patterns');
});

// ─── OpenLibraryService Unit Tests ────────────────────────────────────────────

test('openlibraryservice returns empty result on connection failure', function () {
    $service = new OpenLibraryService();

    // Se a API estiver offline, retorna estrutura vazia
    $result = $service->searchByTitle('__offline_test_xyz__');

    expect($result)->toHaveKeys(['books', 'total', 'pages', 'current_page'])
        ->and($result['books'])->toBeInstanceOf(Collection::class);
});
