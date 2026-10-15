<?php

namespace Modules\Accounts\Filament\Resources\UserResource\Tables;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounts\Filament\Resources\UserResource\Pages;
use App\Models\User;

class UserTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->modifyQueryUsing(fn ($query) => $query->with(['company', 'roles']))
                ->columns([
                    Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('company.name')->label('Company')->searchable()->placeholder('Internal staff'),
                    Tables\Columns\TextColumn::make('company_role')->label('Company Role'),
                    Tables\Columns\TextColumn::make('roles.name')->label('Panel Roles')->badge(),
                ])
                ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
                ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
        }
}


