<?php

namespace Modules\Clients\Filament\Resources;

use App\Support\Modules\ModuleManager;
use Modules\Clients\Filament\Resources\RoleResource\Schemas\RoleForm;
use Modules\Clients\Filament\Resources\RoleResource\Tables\RoleTable;
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

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static string | \UnitEnum | null $navigationGroup = 'Client Management';
    protected static ?int $navigationSort = 30;

    public static function form(Schema $form): Schema
    {
        return RoleForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return RoleTable::table($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_role') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_role') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_role') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_role') ?? false; }
    public static function canDelete($record): bool { return $record->name !== 'administrator' && (auth()->user()?->can('delete_role') ?? false); }
    public static function canDeleteAny(): bool { return false; }
}


