<?php

namespace Modules\SEO\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\SEO\Models\SeoMonitoringRecord;

class SeoContentTypeChart extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'URLs per Content Type';

    protected ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $data = SeoMonitoringRecord::query()
            ->selectRaw('model, count(*) as count')
            ->groupBy('model')
            ->pluck('count', 'model')
            ->toArray();

        return [
            'datasets' => [[
                'label' => 'Count',
                'data' => array_values($data),
                'backgroundColor' => '#3b82f6',
            ]],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
