<?php

namespace Modules\SEO\Filament\Resources\SeoMetaResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\SEO\Filament\Widgets\SeoHealthChart;
use Modules\SEO\Filament\Widgets\SeoOverviewStats;
use Modules\SEO\Filament\Widgets\TopSeoIssues;
use Modules\SEO\Filament\Resources\SeoMetaResource;

class ListSeoMetas extends ListRecords
{
    protected static string $resource = SeoMetaResource::class;

    protected function getHeaderWidgets(): array
    {
        return [SeoOverviewStats::class, SeoHealthChart::class, TopSeoIssues::class];
    }
}
