<?php

// Hanya dua provider yang didaftarkan manual.
// Semua modul ditemukan otomatis oleh ModuleServiceProvider.
return [
    App\Providers\AppServiceProvider::class,
    App\Providers\ModuleServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
];
