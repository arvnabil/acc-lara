<?php

namespace Modules\SEO\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\SEO\Models\SeoMonitoringRecord;

class SeoHealthChart extends ChartWidget
{
    protected ?string $heading = 'SEO Health Breakdown';
    protected static ?int $sort = 2;
    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $query = SeoMonitoringRecord::query()->select(['seo_score', 'model']);

        if ($this->filter && $this->filter !== 'all') {
            $query->where('model', $this->filter);
        }

        $records = $query->get();

        return [
            'datasets' => [[
                'label' => 'SEO Score',
                    'data' => [
                    $records->where('seo_score', '>=', 80)->count(),
                    $records->whereBetween('seo_score', [60, 79])->count(),
                    $records->where('seo_score', '<', 60)->count(),
                ],
                'backgroundColor' => ['#22c55e', '#eab308', '#ef4444'],
                    'hoverOffset' => 4,
            ]],
            'labels' => ['Good (≥80)', 'Warning (60-79)', 'Critical (<60)'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getFilters(): ?array
    {
        return ['all' => 'Pages', 'Product' => 'Products', 'Brand' => 'Brands', 'Category' => 'Categories'];
    }
}
