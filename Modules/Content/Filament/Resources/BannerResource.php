<?php

namespace Modules\Content\Filament\Resources;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Content\Filament\Resources\BannerResource\Pages;
use Modules\Content\Models\Banner;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-photo';
    protected static string | \UnitEnum | null $navigationGroup = 'Content';
    public static function form(Schema $form): Schema { return $form->schema([Section::make('Banner')->schema([
        Forms\Components\TextInput::make('title')->required()->maxLength(255), Forms\Components\TextInput::make('image_url')->required()->url()->maxLength(500),
        Forms\Components\TextInput::make('mobile_image_url')->url()->maxLength(500), Forms\Components\TextInput::make('target_url')->url()->maxLength(500),
        Forms\Components\TextInput::make('sort_order')->numeric()->default(0)->required(), Forms\Components\Toggle::make('is_active')->default(true),
        Forms\Components\DateTimePicker::make('starts_at'), Forms\Components\DateTimePicker::make('expires_at'),
    ])->columns(2)]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\TextColumn::make('title')->searchable()->sortable(), Tables\Columns\ImageColumn::make('image_url'),
        Tables\Columns\TextColumn::make('sort_order')->sortable(), Tables\Columns\IconColumn::make('is_active')->boolean(), Tables\Columns\TextColumn::make('starts_at')->dateTime(), Tables\Columns\TextColumn::make('expires_at')->dateTime(),
    ])->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]); }
    public static function getPages(): array { return ['index' => Pages\ListBanners::route('/'), 'create' => Pages\CreateBanner::route('/create'), 'edit' => Pages\EditBanner::route('/{record}/edit')]; }
    public static function canViewAny(): bool { return auth()->user()?->can('view_any_banner') ?? false; } public static function canView($record): bool { return auth()->user()?->can('view_banner') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('create_banner') ?? false; } public static function canEdit($record): bool { return auth()->user()?->can('update_banner') ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can('delete_banner') ?? false; } public static function canDeleteAny(): bool { return auth()->user()?->can('delete_banner') ?? false; }
}
