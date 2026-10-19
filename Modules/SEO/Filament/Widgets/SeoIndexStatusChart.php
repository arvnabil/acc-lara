<?php

namespace Modules\SEO\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\SEO\Models\SeoMonitoringRecord;

class SeoIndexStatusChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Index Coverage Status';

    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        return [
            'datasets' => [[
                'label' => 'Pages',
                'data' => [
                    SeoMonitoringRecord::query()->where('is_noindex', false)->count(),
                    SeoMonitoringRecord::query()->where('is_noindex', true)->count(),
                ],
                'backgroundColor' => ['#10b981', '#f43f5e'],
            ]],
            'labels' => ['Indexed', 'Noindex'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
