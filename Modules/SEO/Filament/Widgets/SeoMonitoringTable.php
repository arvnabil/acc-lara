<?php

namespace Modules\SEO\Filament\Widgets;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Modules\SEO\Models\SeoMonitoringRecord;

class SeoMonitoringTable extends TableWidget
{
    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'SEO Monitoring Records';

    public function table(Table $table): Table
    {
        return $table
            ->query(SeoMonitoringRecord::query()->latest('updated_at'))
            ->columns([
                TextColumn::make('model')->label('Type')->badge(),
                TextColumn::make('url')->label('Page')->limit(70)->searchable(),
                TextColumn::make('is_noindex')->label('Index Status')->state(fn (SeoMonitoringRecord $record): string => $record->is_noindex ? 'Noindex' : 'Indexed')->badge()->color(fn (SeoMonitoringRecord $record): string => $record->is_noindex ? 'warning' : 'success'),
                IconColumn::make('in_sitemap')->label('In Sitemap')->boolean(),
                IconColumn::make('canonical_valid')->label('Canonical')->boolean(),
                TextColumn::make('seo_score')->label('Score')->suffix('%')->badge()->color(fn (SeoMonitoringRecord $record): string => match (true) {
                    $record->seo_score >= 80 => 'success',
                    $record->seo_score >= 60 => 'warning',
                    default => 'danger',
                }),
                TextColumn::make('last_modified')->label('Last Modified')->dateTime()->sortable(),
            ])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10);
    }
}
