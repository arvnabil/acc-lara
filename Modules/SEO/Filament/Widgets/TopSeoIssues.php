<?php

namespace Modules\SEO\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\Route;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\SEO\Models\SeoMonitoringRecord;

class TopSeoIssues extends TableWidget
{
    protected static ?int $sort = 3;
    protected static bool $isLazy = false;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Top SEO Issues';

    public function table(Table $table): Table
    {
        return $table->query(SeoMonitoringRecord::query()->where('seo_score', '<', 80)->orderBy('seo_score')->limit(5))
            ->columns([
                TextColumn::make('model')->label('Type')->badge(),
                TextColumn::make('page_title')
                    ->label('Page Title')
                    ->state(function (SeoMonitoringRecord $record): string {
                        $modelClass = match ($record->model) {
                            'Product' => Product::class,
                            'Brand' => Brand::class,
                            default => Category::class,
                        };

                        return $modelClass::query()->whereKey($record->model_id)->value('name') ?? $record->url;
                    })
                    ->limit(50),
                TextColumn::make('issue')->label('Issues')->badge()->state(fn (SeoMonitoringRecord $record): string => $record->seo_score < 60 ? 'Critical Score' : 'Meta Missing')->color(fn (SeoMonitoringRecord $record): string => $record->seo_score < 60 ? 'danger' : 'warning'),
                TextColumn::make('seo_score')->label('Score')->badge()->suffix('%')->color(fn (SeoMonitoringRecord $record): string => $record->seo_score >= 80 ? 'success' : ($record->seo_score >= 60 ? 'warning' : 'danger')),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (?SeoMonitoringRecord $record): ?string => $record ? $this->getEditUrl($record) : null)
                    ->hidden(fn (?SeoMonitoringRecord $record): bool => $record === null || $this->getEditUrl($record) === null),
                Action::make('audit')
                    ->label('Audit')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->modalHeading('SEO Audit')
                    ->modalDescription(fn (?SeoMonitoringRecord $record): string => $record === null
                        ? 'SEO audit data is unavailable.'
                        : sprintf(
                            '%s has a SEO score of %d%%. %s',
                            $this->getPageTitle($record),
                            $record->seo_score,
                            $record->seo_score < 60 ? 'Critical score: review the metadata.' : 'Metadata needs attention.',
                        ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->paginated(false);
    }

    protected function getPageTitle(SeoMonitoringRecord $record): string
    {
        $modelClass = match ($record->model) {
            'Product' => Product::class,
            'Brand' => Brand::class,
            default => Category::class,
        };

        return $modelClass::query()->whereKey($record->model_id)->value('name') ?? $record->url;
    }

    protected function getEditUrl(SeoMonitoringRecord $record): ?string
    {
        $routeName = match ($record->model) {
            'Product' => 'filament.admin.resources.products.edit',
            'Brand' => 'filament.admin.resources.brands.edit',
            'Category' => 'filament.admin.resources.categories.edit',
            default => null,
        };

        return $routeName !== null && Route::has($routeName)
            ? route($routeName, ['record' => $record->model_id])
            : null;
    }
}
