<?php

namespace Modules\Accounts\Filament\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounts\Filament\Resources\CompanyResource\Pages;
use Modules\Accounts\Models\Company;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Accounts';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $form): Schema
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_company') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_company') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_company') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_company') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('delete_company') ?? false; }
    public static function canDeleteAny(): bool { return auth()->user()?->can('delete_company') ?? false; }
}
