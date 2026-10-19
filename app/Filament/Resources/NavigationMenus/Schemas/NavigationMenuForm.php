<?php

namespace App\Filament\Resources\NavigationMenus\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class NavigationMenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')->required(),
                Select::make('target_type')->options(['route' => 'Route halaman', 'category' => 'Kategori', 'sale' => 'Promo & Diskon', 'url' => 'URL eksternal'])->live()->required(),
                TextInput::make('target_value')->visible(fn ($get): bool => in_array($get('target_type'), ['route', 'category', 'url'], true)),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
