<?php

namespace Modules\WhatsApp\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\WhatsApp\Models\WhatsAppSetting;

class ManageWhatsAppBubble extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'WhatsApp Bubble';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?string $title = 'WhatsApp Bubble';
    protected string $view = 'whatsapp::filament.pages.manage-whats-app-bubble';
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(WhatsAppSetting::getInstance()->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Core Settings')->schema([
                Toggle::make('is_enabled')->label('Enable Bubble'),
                TextInput::make('phone')->label('WhatsApp Number')->required()->helperText('Format internasional, contoh 6287780116800'),
                Textarea::make('message')->label('Default Message')->rows(3),
                TextInput::make('tooltip')->label('Tooltip Text'),
            ])->columns(2),
            Section::make('Appearance')->schema([
                Select::make('position')->options(['bottom-right' => 'Bottom Right', 'bottom-left' => 'Bottom Left'])->default('bottom-right'),
                Toggle::make('show_online_badge')->label('Show Online Badge'),
                Toggle::make('show_pulse_animation')->label('Show Pulse Animation'),
                Toggle::make('open_in_new_tab')->label('Open In New Tab'),
                TextInput::make('offset_bottom')->label('Offset Bottom'),
                TextInput::make('offset_side')->label('Offset Side'),
                FileUpload::make('icon')->image()->disk('public')->directory('whatsapp'),
                ColorPicker::make('button_color')->label('Button Color'),
            ])->columns(2),
            Section::make('Visibility')->schema([
                CheckboxList::make('active_pages')->options(['all' => 'All Pages', 'homepage' => 'Homepage', 'products' => 'Product Pages'])->default(['all']),
            ]),
        ])->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Save Settings')->submit('save')];
    }

    public function save(): void
    {
        WhatsAppSetting::getInstance()->update($this->form->getState());
        Notification::make()->title('WhatsApp settings saved')->success()->send();
    }
}
