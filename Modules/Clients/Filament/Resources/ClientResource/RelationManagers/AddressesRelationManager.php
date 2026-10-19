<?php

namespace Modules\Clients\Filament\Resources\ClientResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Cart\Services\RajaOngkirShippingService;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';
    protected static ?string $title = 'Client Addresses';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('AddressTabs')->tabs([
                Tabs\Tab::make('Address Details')->schema([
                    TextInput::make('label')->required()->maxLength(50),
                    TextInput::make('recipient_name')->label('Recipient Name')->required()->maxLength(255),
                    TextInput::make('phone')->required()->maxLength(30),
                    TextInput::make('address')->label('Full Address')->required()->maxLength(500)->columnSpanFull(),
                    Toggle::make('is_default')->label('Primary Address'),
                ])->columns(2),
                Tabs\Tab::make('RajaOngkir Location')->schema([
                    Select::make('destination_id')
                        ->label('Pilih Lokasi RajaOngkir')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => collect(app(RajaOngkirShippingService::class)->searchDestinations($search))->mapWithKeys(fn (array $destination): array => [$destination['id'] => $destination['label']])->all())
                        ->getOptionLabelUsing(fn ($value): ?string => filled($value) ? 'RajaOngkir #'.$value : null)
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state): void {
                            $destination = collect(app(RajaOngkirShippingService::class)->searchDestinations((string) $state))->firstWhere('id', (int) $state);
                            if (! $destination) {
                                return;
                            }

                            foreach ([
                                'province' => 'province_name', 'province_name' => 'province_name', 'province_id' => 'province_id',
                                'city' => 'city_name', 'city_name' => 'city_name', 'city_id' => 'city_id',
                                'district_name' => 'district_name', 'district_id' => 'district_id',
                                'subdistrict_name' => 'subdistrict_name', 'subdistrict_id' => 'subdistrict_id',
                                'postal_code' => 'zip_code',
                            ] as $field => $source) {
                                $set($field, $destination[$source]);
                            }
                        })
                        ->helperText('Cari minimal 3 karakter untuk mengisi lokasi dan kode tujuan.')
                        ->columnSpanFull(),
                    TextInput::make('province')->required()->maxLength(100),
                    TextInput::make('province_id')->numeric(),
                    TextInput::make('province_name')->maxLength(150),
                    TextInput::make('city')->label('City / Regency')->required()->maxLength(100),
                    TextInput::make('city_id')->numeric(),
                    TextInput::make('city_name')->maxLength(150),
                    TextInput::make('district_id')->label('District ID')->numeric(),
                    TextInput::make('district_name')->label('District / Kecamatan')->maxLength(150),
                    TextInput::make('subdistrict_id')->label('Subdistrict ID')->numeric(),
                    TextInput::make('subdistrict_name')->label('Subdistrict / Kelurahan')->maxLength(150),
                    TextInput::make('postal_code')->required()->maxLength(10),
                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label')->badge(),
            TextColumn::make('recipient_name')->label('Recipient')->searchable(),
            TextColumn::make('phone'),
            TextColumn::make('city'),
            TextColumn::make('address')->limit(45),
            IconColumn::make('is_default')->label('Primary')->boolean(),
        ])->headerActions([
            CreateAction::make()->mutateFormDataUsing(fn (array $data): array => $this->prepareAddressData($data)),
        ])->recordActions([
            EditAction::make()->mutateFormDataUsing(fn (array $data): array => $this->prepareAddressData($data)),
            DeleteAction::make(),
        ])->defaultSort('is_default', 'desc');
    }

    /** @param array<string, mixed> $data */
    protected function prepareAddressData(array $data): array
    {
        $client = $this->getOwnerRecord();
        $adminId = $client->admin()->value('id') ?? $client->users()->value('id');

        if (($data['is_default'] ?? false) === true) {
            $client->addresses()->update(['is_default' => false]);
        }

        if (! $client->addresses()->exists()) {
            $data['is_default'] = true;
        }

        return [...$data, 'client_id' => $client->id, 'user_id' => $adminId];
    }
}




