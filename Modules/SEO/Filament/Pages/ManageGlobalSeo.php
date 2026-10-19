<?php

namespace Modules\SEO\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\SEO\Models\SeoSetting;

class ManageGlobalSeo extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string|\UnitEnum|null $navigationGroup = 'Seo Management';
    protected static ?string $navigationLabel = 'Global SEO';
    protected static ?string $title = 'Global SEO Settings';
    protected static ?int $navigationSort = 1;
    public ?array $data = [];
    public bool $serviceAccountConfigured = false;

    public function mount(): void
    {
        $setting = SeoSetting::instance();
        $this->serviceAccountConfigured = filled($setting->ga4_service_account_json);
        $data = $setting->toArray();
        unset($data['ga4_service_account_json']);
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Analytics & Tracking')
                ->description('Connect your site to Google Analytics, Tag Manager, and Search Console.')
                ->schema([
                    TextInput::make('ga4_id')->label('Google Analytics 4 ID')->helperText('Your GA4 Measurement ID.'),
                    TextInput::make('gtm_id')->label('Google Tag Manager ID')->helperText('Your GTM Container ID.'),
                    TextInput::make('search_console_verification')->label('Google Search Console Verification')->helperText('Enter only the verification code.'),
                ])->columns(2),
            Section::make('Default Metadata')
                ->description('Fallback meta tags when a page does not have specific SEO data.')
                ->schema([
                    TextInput::make('default_title')->label('Default Meta Title'),
                    Textarea::make('default_description')->label('Default Meta Description')->rows(4)->columnSpanFull(),
                    FileUpload::make('favicon')->label('Site Favicon')->image()->disk('public')->directory('seo')->maxSize(2048),
                    FileUpload::make('default_og_image')->label('Default Open Graph Image')->image()->disk('public')->directory('seo')->maxSize(4096),
                ])->columns(2),
            Section::make('Google Analytics Dashboard API')
                ->description('Configuration for Filament dashboard charts. This is separate from the tracking ID above.')
                ->schema([
                    TextInput::make('ga4_property_id')->label('GA4 Property ID')->placeholder('123456789')->helperText('Get this from GA4 Admin > Property Settings.'),
                    Placeholder::make('service_account_status')->label('Service Account JSON Status')->content(fn (): string => $this->serviceAccountConfigured ? 'Configured. The credential is encrypted and never shown again.' : 'Not configured yet. Paste a Google service account JSON key below.'),
                    Textarea::make('ga4_service_account_json')->label('Replace Service Account JSON (optional)')->rows(8)->helperText('Leave empty to keep the existing credential.')->columnSpanFull(),
                ])->columns(2),
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
        $data = $this->form->getState();
        $credential = $data['ga4_service_account_json'] ?? null;
        unset($data['ga4_service_account_json']);
        if (filled($credential)) {
            $decoded = json_decode($credential, true);
            if (! is_array($decoded) || ! filled($decoded['type'] ?? null) || ! filled($decoded['project_id'] ?? null) || ! filled($decoded['private_key'] ?? null) || ! filled($decoded['client_email'] ?? null)) {
                Notification::make()->danger()->title('Invalid Service Account JSON')->body('The JSON must contain type, project_id, private_key, and client_email.')->send();
                return;
            }
            $data['ga4_service_account_json'] = $credential;
        }
        SeoSetting::instance()->update($data);
        $this->serviceAccountConfigured = filled(SeoSetting::instance()->ga4_service_account_json);
        Notification::make()->title('Global SEO settings saved')->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update_seo_meta') ?? false;
    }
}
