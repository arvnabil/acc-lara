<?php

namespace App\Providers\Filament;

use App\Support\Modules\ModuleManager;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages');

        // Setiap modul aktif menyumbang Resource/Page-nya sendiri ke panel yang sama.
        foreach (app(ModuleManager::class)->enabled() as $module) {
            if (is_dir($module->filamentResourcePath())) {
                $panel->discoverResources(
                    in: $module->filamentResourcePath(),
                    for: $module->namespace().'\\Filament\\Resources',
                );
            }
            if (is_dir($module->filamentPagePath())) {
                $panel->discoverPages(
                    in: $module->filamentPagePath(),
                    for: $module->namespace().'\\Filament\\Pages',
                );
            }
        }

        return $panel;
    }
}
