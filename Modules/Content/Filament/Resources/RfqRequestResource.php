<?php

namespace Modules\Content\Filament\Resources;

use Modules\Content\Filament\Resources\RfqRequestResource\Schemas\RfqRequestForm;
use Modules\Content\Filament\Resources\RfqRequestResource\Tables\RfqRequestTable;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\RfqRequestResource\Pages;
use Modules\Content\Models\RfqRequest;

class RfqRequestResource extends Resource
{
    protected static ?string $model = RfqRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $form): Schema
    {
        return RfqRequestForm::schema($form);
    }

    public static function table(Table $table): Table
    {
        return RfqRequestTable::table($table);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRfqRequests::route('/'), 'edit' => Pages\EditRfqRequest::route('/{record}/edit')];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_rfq_request') ?? false;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->can('view_rfq_request') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_rfq') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
