<?php

namespace Modules\Email\Filament\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;

class EmailSettingsForm
{
    /**
     * @return array<int, Section>
     */
    public static function components(): array
    {
        return [
            Section::make('SMTP Configuration')
                ->description('Configure the mail transport used by application notifications.')
                ->schema([
                    TextInput::make('mailer')->label('Mailer')->default('smtp')->required(),
                    TextInput::make('smtp_host')->label('SMTP Host')->required(),
                    TextInput::make('smtp_port')->label('SMTP Port')->numeric()->default(587)->required(),
                    TextInput::make('smtp_username')->label('SMTP Username'),
                    TextInput::make('smtp_password')
                        ->label('SMTP Password')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                    TextInput::make('from_address')->label('From Email')->email()->required(),
                    TextInput::make('from_name')->label('From Name')->required(),
                    TextInput::make('admin_email')->label('Admin Notification Email')->email(),
                ])
                ->columns(2),
            Section::make('Notification Events')
                ->description('Choose which events should send email notifications.')
                ->schema([
                    Toggle::make('order_created_enabled')->label('Order Created')->inline(false),
                    Toggle::make('payment_received_enabled')->label('Payment Received')->inline(false),
                    Toggle::make('points_awarded_enabled')->label('Points Awarded')->inline(false),
                    Toggle::make('order_status_enabled')->label('Order Status Changed')->inline(false),
                    Toggle::make('password_reset_enabled')->label('Password Reset')->inline(false),
                ])
                ->columns(2),
        ];
    }
}
