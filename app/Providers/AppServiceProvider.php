<?php

namespace App\Providers;

use App\Models\FavoriteBook;
use App\Policies\FavoriteBookPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * AppServiceProvider
 *
 * Registra bindings, policies e configurações globais da aplicação.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registra a policy de FavoriteBook
        Gate::policy(FavoriteBook::class, FavoriteBookPolicy::class);
    }
}
