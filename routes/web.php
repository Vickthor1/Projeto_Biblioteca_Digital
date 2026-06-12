<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteBookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas Públicas
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : view('welcome');
})->name('home');

/*
|--------------------------------------------------------------------------
| Rotas Protegidas (requerem autenticação)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        $recentFavorites = auth()->user()
            ->favoriteBooks()
            ->latest()
            ->take(4)
            ->get();

        $totalFavorites = auth()->user()->favoriteBooks()->count();

        return view('dashboard', compact('recentFavorites', 'totalFavorites'));
    })->name('dashboard');

    // Pesquisa de livros
    Route::controller(BookController::class)->prefix('books')->name('books.')->group(function () {
        Route::get('/search',  'search')->name('search');
        Route::get('/results', 'results')->name('results');
    });

    // Biblioteca pessoal (favoritos)
    Route::controller(FavoriteBookController::class)->prefix('favorites')->name('favorites.')->group(function () {
        Route::get('/',          'index')->name('index');
        Route::post('/',         'store')->name('store');
        Route::delete('/{favorite}', 'destroy')->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação (Laravel Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__ . '/auth.php';
