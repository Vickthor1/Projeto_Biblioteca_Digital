<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_favorite_books_table
 *
 * Cria a tabela que armazena os livros favoritos de cada usuário.
 * O índice único (user_id, open_library_id) impede duplicações.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_books', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->string('open_library_id');   // Ex: "/works/OL82563W"
            $table->string('title');
            $table->string('author')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->string('isbn', 20)->nullable();
            $table->string('cover_url')->nullable();

            $table->timestamps();

            // Impede que o mesmo usuário salve o mesmo livro duas vezes
            $table->unique(['user_id', 'open_library_id']);

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_books');
    }
};
