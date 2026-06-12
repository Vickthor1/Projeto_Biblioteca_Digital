<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de Autenticação
|--------------------------------------------------------------------------
| Compatível com Laravel Breeze. Ao instalar o Breeze via
| `php artisan breeze:install blade`, este arquivo é gerado
| automaticamente com controllers já configurados.
|
| Para instalação manual sem Breeze, os controllers abaixo
| devem ser criados conforme a documentação oficial.
|--------------------------------------------------------------------------
*/

// Registro
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    // Login
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

// Logout
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
