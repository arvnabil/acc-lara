<?php

namespace Modules\SEO\Filament\Resources\SeoWhitelistDomainResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class SeoWhitelistDomainForm
{
    public static function schema(): array
    {
        return [Section::make('Domain Information')->schema([
            TextInput::make('domain')->required()->unique(ignoreRecord: true)->helperText('Domain tanpa http:// atau https://.'),
            Textarea::make('description')->rows(3),
            Toggle::make('is_active')->label('Active')->default(true),
        ])->columns(2)];
    }
}
