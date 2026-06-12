<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreFavoriteBookRequest
 *
 * Valida e sanitiza os dados enviados ao salvar um livro favorito.
 * Os dados chegam do formulário oculto no card de resultado de busca.
 */
class StoreFavoriteBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'open_library_id'  => ['required', 'string', 'regex:/^\/works\/[A-Za-z0-9]+$/', 'max:100'],
            'title'            => ['required', 'string', 'min:1', 'max:500'],
            'author'           => ['nullable', 'string', 'max:255'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'isbn'             => ['nullable', 'string', 'regex:/^[0-9\-]+$/', 'max:20'],
            'cover_url'        => ['nullable', 'url', 'starts_with:https://', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'open_library_id.required' => 'Identificador do livro é obrigatório.',
            'title.required'           => 'O título do livro é obrigatório.',
        ];
    }
}
