<?php

namespace Modules\Promo\Filament\Resources\PromoCodeResource\Schemas;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Promo\Filament\Resources\PromoCodeResource\Pages;
use Modules\Promo\Models\PromoCode;

class PromoCodeForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([
                Section::make('Promo Code')->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()->maxLength(50)->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn (?string $state): string => strtoupper(trim($state ?? ''))),
                    Forms\Components\TextInput::make('label')->required()->maxLength(255),
                    Forms\Components\Select::make('type')->required()->options(['percent' => 'Percent', 'fixed' => 'Fixed'])->live(),
                    Forms\Components\TextInput::make('value')->required()->numeric()->minValue(0),
                    Forms\Components\TextInput::make('min_order')->label('Minimum Order')->required()->numeric()->minValue(0)->default(0),
                    Forms\Components\TextInput::make('max_uses')->label('Quota')->numeric()->minValue(1)->nullable(),
                    Forms\Components\TextInput::make('used_count')->label('Used Count')->numeric()->minValue(0)->default(0)->readOnly(),
                    Forms\Components\DateTimePicker::make('valid_from')->label('Valid From'),
                    Forms\Components\DateTimePicker::make('valid_until')->label('Valid Until')->after('valid_from'),
                    Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                ])->columns(2),
            ]);
        }
}


