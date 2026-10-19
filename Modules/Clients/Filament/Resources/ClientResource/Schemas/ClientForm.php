<?php

namespace Modules\Clients\Filament\Resources\ClientResource\Schemas;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Clients\Filament\Resources\ClientResource\Pages;
use Modules\Clients\Models\Client;

class ClientForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([
                Section::make('Client')->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\Toggle::make('is_verified')->label('Terverifikasi'),
                ])->columns(2),
                Section::make('Referral Information')->schema([
                    Placeholder::make('referral_code')
                        ->label('Kode Referral')
                        ->content(fn (?Client $record): string => $record?->admin?->referral_code ?? 'Belum ada'),
                    Placeholder::make('referral_owner')
                        ->label('Pemilik Kode')
                        ->content(fn (?Client $record): string => $record?->admin?->name ?? 'Belum ada'),
                ])->columns(2),
            ]);
        }
}







