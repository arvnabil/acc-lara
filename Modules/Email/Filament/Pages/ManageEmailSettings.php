<?php

namespace Modules\Email\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Artisan;
use Modules\Email\Filament\Schemas\EmailSettingsForm;
use Modules\Email\Models\EmailSetting;

class ManageEmailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Email Settings';
    protected static ?string $title = 'Email Settings';
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(EmailSetting::getInstance()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(EmailSettingsForm::components())
            ->statePath('data');
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
        return [Action::make('save')->label('Save Email Settings')->submit('save')];
    }

    public function save(): void
    {
        EmailSetting::getInstance()->update($this->form->getState());
        Artisan::call('queue:restart');
        Notification::make()->title('Email settings saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage_email_settings') ?? false;
    }
}
