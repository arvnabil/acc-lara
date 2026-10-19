<?php

namespace Modules\Clients\Filament\Resources\RoleResource\Tables;

use App\Support\Modules\ModuleManager;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Modules\Clients\Filament\Resources\RoleResource\Pages;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleTable
{
    public static function table(Table $table): Table
        {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                    Tables\Columns\TextColumn::make('permissions_count')->counts('permissions')->label('Permissions')->sortable(),
                    Tables\Columns\TextColumn::make('users_count')->counts('users')->label('Users')->sortable(),
                    Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                ])
                ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()->disabled(fn (Role $record): bool => $record->name === 'administrator')])
                ->bulkActions([]);
        }
}




