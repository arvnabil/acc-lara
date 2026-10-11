<?php

namespace Modules\Accounts\Filament\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Accounts\Filament\Resources\UserResource\Pages;
use App\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static string | \UnitEnum | null $navigationGroup = 'Accounts';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('User')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')->password()->revealable()->label('Password')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Forms\Components\TextInput::make('phone')->tel()->maxLength(30),
                Forms\Components\Select::make('company_id')->label('Storefront Company')
                    ->relationship('company', 'name')->searchable()->preload()->nullable(),
                Forms\Components\Select::make('company_role')->label('Company Role')
                    ->options(['admin' => 'Admin', 'member' => 'Member'])->nullable(),
                Forms\Components\Select::make('roles')->label('Panel Roles')
                    ->relationship('roles', 'name')->multiple()->searchable()->preload(),
            ])->columns(2),
        ]);
    }

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_user') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_user') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_user') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_user') ?? false; }
    public static function canDelete($record): bool { return $record->id !== auth()->id() && (auth()->user()?->can('delete_user') ?? false); }
    public static function canDeleteAny(): bool { return auth()->user()?->can('delete_user') ?? false; }
}
