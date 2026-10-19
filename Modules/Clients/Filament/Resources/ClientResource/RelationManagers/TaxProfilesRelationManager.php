<?php

namespace Modules\Clients\Filament\Resources\ClientResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxProfilesRelationManager extends RelationManager
{
    protected static string $relationship = 'taxProfiles';

    protected static ?string $title = 'NPWP / Tax Profiles';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Nama Profil')->required()->maxLength(80),
            TextInput::make('npwp')->label('NPWP')->required()->maxLength(30),
            TextInput::make('taxpayer_name')->label('Nama Wajib Pajak')->required()->maxLength(255),
            Textarea::make('taxpayer_address')->label('Alamat Wajib Pajak')->rows(3)->columnSpanFull(),
            FileUpload::make('document_path')
                ->label('Dokumen NPWP')
                ->disk('public')
                ->directory('tax-profiles')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120),
            Toggle::make('is_default')->label('Gunakan sebagai NPWP utama'),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Profil')->searchable()->sortable(),
                TextColumn::make('npwp')->label('NPWP')->searchable(),
                TextColumn::make('taxpayer_name')->label('Nama Wajib Pajak')->searchable(),
                IconColumn::make('is_default')->label('Utama')->boolean(),
                TextColumn::make('document_path')->label('Dokumen')->formatStateUsing(fn (?string $state): string => filled($state) ? 'Tersedia' : 'Belum ada'),
            ])
            ->headerActions([
                CreateAction::make()->mutateFormDataUsing(fn (array $data): array => $this->prepareTaxProfileData($data)),
            ])
            ->recordActions([
                EditAction::make()->mutateFormDataUsing(fn (array $data): array => $this->prepareTaxProfileData($data)),
                DeleteAction::make(),
            ])
            ->defaultSort('is_default', 'desc');
    }

    /** @param array<string, mixed> $data */
    protected function prepareTaxProfileData(array $data): array
    {
        $client = $this->getOwnerRecord();

        if (($data['is_default'] ?? false) === true) {
            $client->taxProfiles()->update(['is_default' => false]);
        }

        if (! $client->taxProfiles()->exists()) {
            $data['is_default'] = true;
        }

        return $data;
    }
}
