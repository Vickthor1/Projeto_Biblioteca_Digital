<?php

namespace Database\Factories;

use App\Models\FavoriteBook;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para geração de FavoriteBook nos testes.
 */
class FavoriteBookFactory extends Factory
{
    protected $model = FavoriteBook::class;

    public function definition(): array
    {
        $isbn = $this->faker->isbn13();

        return [
            'user_id'          => User::factory(),
            'open_library_id'  => '/works/OL' . $this->faker->unique()->numerify('######W'),
            'title'            => $this->faker->sentence(3),
            'author'           => $this->faker->name(),
            'publication_year' => $this->faker->numberBetween(1900, 2024),
            'isbn'             => $isbn,
            'cover_url'        => "https://covers.openlibrary.org/b/isbn/{$isbn}-M.jpg",
        ];
    }
}
