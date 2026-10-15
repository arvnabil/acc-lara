<?php

namespace Modules\Accounts\Filament\Resources;

use Modules\Accounts\Filament\Resources\UserResource\Schemas\UserForm;
use Modules\Accounts\Filament\Resources\UserResource\Tables\UserTable;
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
        return UserForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return UserTable::table($table);
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
