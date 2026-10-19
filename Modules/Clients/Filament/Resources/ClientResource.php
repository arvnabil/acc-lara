<?php

namespace Modules\Clients\Filament\Resources;

use Modules\Clients\Filament\Resources\ClientResource\Schemas\ClientForm;
use Modules\Clients\Filament\Resources\ClientResource\Tables\ClientTable;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Clients\Filament\Resources\ClientResource\Pages;
use Modules\Clients\Models\Client;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string | \UnitEnum | null $navigationGroup = 'Client Management';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?string $pluralModelLabel = 'Clients';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $form): Schema
    {
        return ClientForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return ClientTable::table($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('admin');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ClientResource\RelationManagers\AddressesRelationManager::class,
            ClientResource\RelationManagers\TaxProfilesRelationManager::class,
        ];
    }

    public static function canViewAny(): bool { return auth()->user()?->can('view_any_client') ?? false; }
    public static function canView($record): bool { return auth()->user()?->can('view_client') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_client') ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can('update_client') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('delete_client') ?? false; }
    public static function canDeleteAny(): bool { return auth()->user()?->can('delete_client') ?? false; }
}




