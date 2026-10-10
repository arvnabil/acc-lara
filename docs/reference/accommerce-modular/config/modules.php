<?php

return [
    // Folder modul (native, tanpa nwidart/laravel-modules)
    'path' => base_path('Modules'),

    // Namespace root modul (harus cocok dengan PSR-4 di composer.json)
    'namespace' => 'Modules',

    // Cache hasil scan ke bootstrap/cache/modules.php di produksi (php artisan module:cache)
    'cache' => env('MODULES_CACHE', false),
];
