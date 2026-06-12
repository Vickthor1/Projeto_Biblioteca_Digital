<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model FavoriteBook
 *
 * Representa um livro salvo na biblioteca pessoal de um usuário.
 * Os dados são persistidos localmente a partir da Open Library API.
 *
 * @property int    $id
 * @property int    $user_id
 * @property string $open_library_id
 * @property string $title
 * @property string|null $author
 * @property int|null    $publication_year
 * @property string|null $isbn
 * @property string|null $cover_url
 */
class FavoriteBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'open_library_id',
        'title',
        'author',
        'publication_year',
        'isbn',
        'cover_url',
    ];

    protected $casts = [
        'publication_year' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    /**
     * Um livro favorito pertence a um único usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Retorna a URL da capa ou uma imagem padrão caso não exista.
     */
    public function getCoverAttribute(): string
    {
        return $this->cover_url
            ?? asset('images/no-cover.svg');
    }
}
