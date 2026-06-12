<?php

namespace App\Policies;

use App\Models\FavoriteBook;
use App\Models\User;

/**
 * FavoriteBookPolicy
 *
 * Garante que apenas o dono do livro favorito
 * possa visualizá-lo ou excluí-lo.
 */
class FavoriteBookPolicy
{
    /**
     * Somente o dono do registro pode ver o favorito.
     */
    public function view(User $user, FavoriteBook $favoriteBook): bool
    {
        return $user->id === $favoriteBook->user_id;
    }

    /**
     * Somente o dono do registro pode excluir o favorito.
     */
    public function delete(User $user, FavoriteBook $favoriteBook): bool
    {
        return $user->id === $favoriteBook->user_id;
    }
}
