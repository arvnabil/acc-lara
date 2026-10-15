<?php

namespace Modules\Accounts\Filament\Resources\CompanyResource\Schemas;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounts\Filament\Resources\CompanyResource\Pages;
use Modules\Accounts\Models\Company;

class CompanyForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([
                Section::make('Company')->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('npwp')
                        ->label('NPWP')
                        ->maxLength(30)
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('sppkp')->maxLength(50),
                    Forms\Components\TextInput::make('phone')->tel()->maxLength(30),
                    Forms\Components\TextInput::make('email')->email()->maxLength(255),
                    Forms\Components\Textarea::make('address')->rows(3)->columnSpanFull(),
                    Forms\Components\Toggle::make('is_verified')->label('Terverifikasi'),
                ])->columns(2),
            ]);
        }
}



