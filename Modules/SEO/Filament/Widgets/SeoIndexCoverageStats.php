<?php

namespace Modules\SEO\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\SEO\Models\SeoMonitoringRecord;

class SeoIndexCoverageStats extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $records = SeoMonitoringRecord::query()->get(['is_noindex', 'in_sitemap']);

        return [
            Stat::make('Indexed', number_format($records->where('is_noindex', false)->count()))->description('Pages visible to search engines')->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Noindex', number_format($records->where('is_noindex', true)->count()))->description('Pages excluded from indexing')->descriptionIcon('heroicon-m-eye-slash')->color('warning'),
            Stat::make('Not in Sitemap', number_format($records->where('in_sitemap', false)->count()))->description('Pages missing from sitemap')->descriptionIcon('heroicon-m-exclamation-triangle')->color('danger'),
        ];
    }
}
