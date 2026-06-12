<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User
 *
 * Estende o Authenticatable padrão do Laravel,
 * adicionando o relacionamento com a biblioteca pessoal.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Um usuário possui muitos livros favoritos.
     */
    public function favoriteBooks(): HasMany
    {
        return $this->hasMany(FavoriteBook::class);
    }

    // ─── Helper Methods ───────────────────────────────────────────────────────

    /**
     * Verifica se um livro da Open Library já está nos favoritos do usuário.
     */
    public function hasFavorite(string $openLibraryId): bool
    {
        return $this->favoriteBooks()
            ->where('open_library_id', $openLibraryId)
            ->exists();
    }
}
