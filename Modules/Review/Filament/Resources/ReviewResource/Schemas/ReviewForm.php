<?php

namespace Modules\Review\Filament\Resources\ReviewResource\Schemas;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Review\Filament\Resources\ReviewResource\Pages;
use Modules\Review\Models\Review;

class ReviewForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([
                Forms\Components\Toggle::make('is_approved')->label('Approved'),
                Forms\Components\Textarea::make('media')
                    ->label('Media')
                    ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                    ->disabled()
                    ->dehydrated(false),
            ]);
        }
}


