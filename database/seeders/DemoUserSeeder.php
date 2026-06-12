<?php

namespace Database\Seeders;

use App\Models\FavoriteBook;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoUserSeeder
 *
 * Cria um usuário de demonstração com livros favoritos pré-salvos
 * para facilitar a apresentação acadêmica do projeto.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@biblioteca.com'],
            [
                'name'     => 'Usuário Demo',
                'password' => Hash::make('password'),
            ]
        );

        $books = [
            [
                'open_library_id'  => '/works/OL82563W',
                'title'            => 'Dom Quixote',
                'author'           => 'Miguel de Cervantes',
                'publication_year' => 1605,
                'isbn'             => '9780142437230',
                'cover_url'        => 'https://covers.openlibrary.org/b/isbn/9780142437230-M.jpg',
            ],
            [
                'open_library_id'  => '/works/OL45883W',
                'title'            => '1984',
                'author'           => 'George Orwell',
                'publication_year' => 1949,
                'isbn'             => '9780451524935',
                'cover_url'        => 'https://covers.openlibrary.org/b/isbn/9780451524935-M.jpg',
            ],
            [
                'open_library_id'  => '/works/OL262758W',
                'title'            => 'O Hobbit',
                'author'           => 'J.R.R. Tolkien',
                'publication_year' => 1937,
                'isbn'             => '9780547928227',
                'cover_url'        => 'https://covers.openlibrary.org/b/isbn/9780547928227-M.jpg',
            ],
            [
                'open_library_id'  => '/works/OL893932W',
                'title'            => 'Dune',
                'author'           => 'Frank Herbert',
                'publication_year' => 1965,
                'isbn'             => '9780441013593',
                'cover_url'        => 'https://covers.openlibrary.org/b/isbn/9780441013593-M.jpg',
            ],
        ];

        foreach ($books as $book) {
            FavoriteBook::firstOrCreate(
                [
                    'user_id'        => $user->id,
                    'open_library_id' => $book['open_library_id'],
                ],
                $book
            );
        }

        $this->command->info("✅ Demo user created: demo@biblioteca.com / password");
        $this->command->info("📚 {$user->favoriteBooks()->count()} sample books added.");
    }
}
