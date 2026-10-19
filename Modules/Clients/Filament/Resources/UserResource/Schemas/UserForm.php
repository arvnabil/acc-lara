<?php

namespace Modules\Clients\Filament\Resources\UserResource\Schemas;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Clients\Filament\Resources\UserResource\Pages;
use App\Models\User;

class UserForm
{
    public static function schema(Schema $form): Schema
        {
            return $form->schema([
                Section::make('User')->schema([
                    Forms\Components\TextInput::make('name')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('password')->password()->revealable()->label('Password')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $operation): bool => $operation === 'create'),
                    Forms\Components\TextInput::make('phone')->tel()->maxLength(30),
                    Forms\Components\Select::make('client_id')->label('Storefront Client')
                        ->relationship('client', 'name')->searchable()->preload()->nullable(),
                    Forms\Components\Select::make('company_role')->label('Client Role')
                        ->options(['admin' => 'Admin', 'member' => 'Member'])->nullable(),
                    Forms\Components\Select::make('roles')->label('Panel Roles')
                        ->relationship('roles', 'name')->multiple()->searchable()->preload(),
                ])->columns(2),
            ]);
        }
}






