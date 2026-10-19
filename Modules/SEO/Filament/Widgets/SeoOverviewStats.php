<?php

namespace Modules\SEO\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\SEO\Models\SeoMonitoringRecord;

class SeoOverviewStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $query = SeoMonitoringRecord::query();
        $indexed = (clone $query)->where('is_noindex', false)->count();
        $missing = (clone $query)->where('seo_score', '<', 80)->count();
        $average = round((float) ((clone $query)->avg('seo_score') ?? 0), 1);
        $critical = (clone $query)->where('seo_score', '<', 60)->count();

        return [
            Stat::make('Total Indexed Pages', number_format($indexed))
                ->description('Pages visible to search engines')->descriptionIcon('heroicon-m-eye')->color('success'),
            Stat::make('Pages with Missing Meta', number_format($missing))
                ->description('Missing title or description')->descriptionIcon('heroicon-m-exclamation-triangle')->color($missing > 0 ? 'warning' : 'success'),
            Stat::make('Average SEO Score', number_format($average, 1))
                ->description('Based on metadata health')->descriptionIcon('heroicon-m-chart-bar')->color($average >= 80 ? 'success' : ($average >= 60 ? 'warning' : 'danger')),
            Stat::make('Critical SEO Issues', number_format($critical))
                ->description('Score < 60')->descriptionIcon('heroicon-m-x-circle')->color($critical > 0 ? 'danger' : 'success'),
        ];
    }
}
