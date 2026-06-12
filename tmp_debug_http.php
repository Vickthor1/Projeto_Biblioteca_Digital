<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Bootstrap\RegisterFacades;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Cache::flush();

Http::fake([
    'https://openlibrary.org/search.json' => Http::response([
        'numFound' => 2,
        'docs' => [
            [
                'key' => '/works/OL1W',
                'title' => 'Book One',
                'author_name' => ['Author One'],
                'first_publish_year' => 2020,
                'isbn' => ['1111111111'],
            ],
            [
                'key' => '/works/OL2W',
                'title' => 'Book Two',
                'author_name' => ['Author Two'],
                'first_publish_year' => 2021,
                'isbn' => ['2222222222'],
            ],
        ],
    ]),
]);

$response = Http::timeout(10)->get('https://openlibrary.org/search.json', [
    'q' => 'test',
    'limit' => 12,
    'offset' => 0,
    'fields' => 'key,title,author_name,first_publish_year,isbn,cover_i',
]);

var_dump([
    'failed' => $response->failed(),
    'status' => $response->status(),
    'body' => $response->body(),
    'json' => $response->json(),
]);

$service = new App\Services\OpenLibraryService();
$result = $service->searchByTitle('test', 1);
var_dump($result);
