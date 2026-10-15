<?php

namespace Modules\Content\Filament\Resources\RfqRequestResource\Tables;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\RfqRequestResource\Pages;
use Modules\Content\Models\RfqRequest;

class RfqRequestTable
{
    public static function table(Table $table): Table
        {
            return $table->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('email')->searchable(), Tables\Columns\TextColumn::make('company_name')->searchable(), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])->filters([Tables\Filters\SelectFilter::make('status')->options(['new' => 'New', 'in_progress' => 'In Progress', 'quoted' => 'Quoted', 'closed' => 'Closed'])])->actions([Actions\ViewAction::make(), Actions\EditAction::make()])->defaultSort('created_at', 'desc');
        }
}


