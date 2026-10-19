<?php

namespace Modules\Payments\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Payments\Models\PaymentSetting;

class ManagePaymentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|\UnitEnum|null $navigationGroup = 'Payments';
    protected static ?string $navigationLabel = 'Payment Settings';
    protected static ?string $title = 'Payment Settings';
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(PaymentSetting::getInstance()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Payment Methods')->schema([
                Toggle::make('midtrans_enabled')->label('Enable Midtrans'),
                Toggle::make('qris_enabled')->label('Enable QRIS Manual'),
                Toggle::make('bank_transfer_enabled')->label('Enable Bank Transfer Manual'),
            ])->columns(3),
            Section::make('QRIS Manual')->schema([
                FileUpload::make('qris_image')->label('QRIS Image')->image()->disk('public')->directory('payments/qris'),
                TextInput::make('qris_nmid')->label('NMID')->maxLength(50),
                TextInput::make('merchant_name')->label('Merchant Name')->required(),
            ])->columns(2),
            Section::make('Bank Transfer Accounts')->schema([
                Repeater::make('bank_accounts')->label('Bank Accounts')->schema([
                    TextInput::make('id')->label('Code')->required()->maxLength(30),
                    TextInput::make('name')->label('Bank Name')->required(),
                    TextInput::make('account_name')->label('Account Name')->required(),
                    TextInput::make('account_number')->label('Account Number')->required(),
                ])->columns(2)->default(config('payment.banks', []))->reorderable(false),
            ]),
        ])->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->alignment($this->getFormActionsAlignment())
                        ->fullWidth($this->hasFullWidthFormActions())
                        ->sticky($this->areFormActionsSticky())
                        ->key('form-actions'),
                ]),
        ]);
    }

    protected function hasFullWidthFormActions(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Save Settings')->submit('save')];
    }

    public function save(): void
    {
        PaymentSetting::getInstance()->update($this->form->getState());
        Notification::make()->title('Payment settings saved')->success()->send();
    }
}
