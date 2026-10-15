<?php

namespace Modules\Accounts\Filament\Resources\CompanyResource\Tables;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounts\Filament\Resources\CompanyResource\Pages;
use Modules\Accounts\Models\Company;

class CompanyTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('npwp')->label('NPWP')->searchable(),
                    Tables\Columns\TextColumn::make('email')->searchable(),
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


