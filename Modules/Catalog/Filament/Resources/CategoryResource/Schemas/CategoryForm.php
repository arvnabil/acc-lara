<?php

namespace Modules\Catalog\Filament\Resources\CategoryResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Catalog\Filament\Resources\CategoryResource\Pages;
use Modules\Catalog\Models\Category;
use Modules\SEO\Filament\Resources\SeoMetaResource\Schemas\SeoForm;

class CategoryForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([
                Tabs::make('CategoryTabs')
                    ->tabs([
                        Tabs\Tab::make('Category Details')
                            ->schema([
                Section::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state ?? ''))),
    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
    
                    Forms\Components\Select::make('parent_id')
                        ->label('Parent Category')
                        ->relationship('parent', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),
    
                    Forms\Components\TextInput::make('full_path')
                        ->maxLength(500)
                        ->nullable(),
    
                    Forms\Components\Toggle::make('is_addon')
                        ->label('Is Add-on Category'),
    
                    Forms\Components\Toggle::make('is_brand_group')
                        ->label('Is Brand Group'),
    
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                ])->columns(2),
                            ]),
                        Tabs\Tab::make('SEO')->schema([
                            Group::make()->relationship('seo')->schema(SeoForm::schema()),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
        }
}


