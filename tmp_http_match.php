<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

Http::fake([
    'https://openlibrary.org/search.json' => Http::response(['ok' => true], 200),
]);

$response = Http::get('https://openlibrary.org/search.json', ['q' => 'test']);
var_dump($response->json());
