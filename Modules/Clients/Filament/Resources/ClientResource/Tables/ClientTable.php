<?php

namespace Modules\Clients\Filament\Resources\ClientResource\Tables;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Clients\Filament\Resources\ClientResource\Pages;
use Modules\Clients\Models\Client;

class ClientTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('admin.email')
                        ->label('Email Admin')
                        ->searchable()
                        ->placeholder('Belum ada'),
                    Tables\Columns\TextColumn::make('admin.referral_code')
                        ->label('Kode Referral')
                        ->searchable()
                        ->copyable()
                        ->placeholder('Belum ada'),
                    Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Users')->sortable(),
                    Tables\Columns\IconColumn::make('is_verified')->boolean()->label('Verified'),
                    Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                ])
                ->filters([
                    Tables\Filters\TernaryFilter::make('is_verified')->label('Verification'),
                ])
                ->actions([
                    Actions\EditAction::make(),
                    Actions\DeleteAction::make(),
                ])
                ->bulkActions([
                    Actions\BulkActionGroup::make([
                        Actions\DeleteBulkAction::make(),
                    ]),
                ])
                ->defaultSort('name');
        }
}






