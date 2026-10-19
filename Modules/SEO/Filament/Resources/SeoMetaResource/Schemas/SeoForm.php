<?php

namespace Modules\SEO\Filament\Resources\SeoMetaResource\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Modules\SEO\Models\SeoMeta;
use Modules\SEO\Services\Seo\SeoService;

class SeoForm
{
    public static function schema(): array
    {
        return [
            Section::make('Basic SEO')->schema([
                TextInput::make('title')->maxLength(255),
                Textarea::make('description')->rows(4)->columnSpanFull(),
                TextInput::make('canonical_url')->url()->maxLength(500),
                Toggle::make('noindex')->label('No index'),
            ])->columns(2),
            Section::make('Social sharing')->schema([
                TextInput::make('og_title')->maxLength(255),
                Textarea::make('og_description')->rows(3),
                FileUpload::make('og_image')->image()->disk('public')->directory('seo'),
                TextInput::make('twitter_card')->default('summary_large_image'),
            ])->columns(2),
            Section::make('Structured data')->schema([
                Textarea::make('schema_override')
                    ->label('JSON-LD Structured Data')
                    ->helperText('Auto-generated schema. Click Generate to save a snapshot, or Clear to use dynamic generation.')
                    ->rows(10)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'font-mono text-sm'])
                    ->rule('json')
                    ->formatStateUsing(fn (mixed $state): ?string => is_array($state)
                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                        : $state)
                    ->dehydrateStateUsing(fn (?string $state): ?array => blank($state) ? null : json_decode($state, true)),
            ])->headerActions([
                Action::make('generate_json_ld')
                    ->label('Generate JSON-LD')
                    ->icon('heroicon-o-code-bracket')
                    ->action(function (Set $set, mixed $record): void {
                        $seoMeta = $record instanceof SeoMeta ? $record : $record?->seo;
                        $model = $seoMeta?->seoable ?? $record;

                        if (! $model instanceof \Illuminate\Database\Eloquent\Model) {
                            Notification::make()->warning()->title('Save the page before generating JSON-LD.')->send();
                            return;
                        }

                        $json = app(SeoService::class)->for($model)['schema'];
                        $set('schema_override', json_encode([
                            '@context' => 'https://schema.org',
                            '@graph' => $json,
                        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                        Notification::make()->success()->title('JSON-LD generated successfully.')->send();
                    }),
                Action::make('clear_json_ld')
                    ->label('Clear (Use Dynamic)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Set $set): mixed => $set('schema_override', null)),
            ]),
        ];
    }
}
