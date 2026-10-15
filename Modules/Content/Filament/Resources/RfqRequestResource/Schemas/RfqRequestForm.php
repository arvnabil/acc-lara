<?php

namespace Modules\Content\Filament\Resources\RfqRequestResource\Schemas;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\RfqRequestResource\Pages;
use Modules\Content\Models\RfqRequest;

class RfqRequestForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([Section::make('RFQ')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255), Forms\Components\TextInput::make('email')->email()->required()->maxLength(255), Forms\Components\TextInput::make('phone')->required()->maxLength(30), Forms\Components\TextInput::make('company_name')->required()->maxLength(255),
                Forms\Components\Select::make('status')->options(['new' => 'New', 'in_progress' => 'In Progress', 'quoted' => 'Quoted', 'closed' => 'Closed'])->required(), Forms\Components\Textarea::make('message')->columnSpanFull(),
            ])->columns(2)]);
        }
}


