<?php

namespace Modules\Content\Filament\Resources;

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
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';
    public static function form(Schema $form): Schema { return $form->schema([Section::make('RFQ')->schema([
        Forms\Components\TextInput::make('name')->required()->maxLength(255), Forms\Components\TextInput::make('email')->email()->required()->maxLength(255), Forms\Components\TextInput::make('phone')->required()->maxLength(30), Forms\Components\TextInput::make('company_name')->required()->maxLength(255),
        Forms\Components\Select::make('status')->options(['new' => 'New', 'in_progress' => 'In Progress', 'quoted' => 'Quoted', 'closed' => 'Closed'])->required(), Forms\Components\Textarea::make('message')->columnSpanFull(),
    ])->columns(2)]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('name')->searchable()->sortable(), Tables\Columns\TextColumn::make('email')->searchable(), Tables\Columns\TextColumn::make('company_name')->searchable(), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
    ])->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])])->defaultSort('created_at', 'desc'); }
    public static function getPages(): array { return ['index' => Pages\ListRfqRequests::route('/'), 'create' => Pages\CreateRfqRequest::route('/create'), 'edit' => Pages\EditRfqRequest::route('/{record}/edit')]; }
    public static function canViewAny(): bool { return auth()->user()?->can('view_any_rfq_request') ?? false; } public static function canView($record): bool { return auth()->user()?->can('view_rfq_request') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_rfq_request') ?? false; } public static function canEdit($record): bool { return auth()->user()?->can('update_rfq_request') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('delete_rfq_request') ?? false; } public static function canDeleteAny(): bool { return auth()->user()?->can('delete_rfq_request') ?? false; }
}
