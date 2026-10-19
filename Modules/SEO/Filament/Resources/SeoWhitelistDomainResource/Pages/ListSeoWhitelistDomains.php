<?php

namespace Modules\SEO\Filament\Resources\SeoWhitelistDomainResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\SEO\Filament\Resources\SeoWhitelistDomainResource;

class ListSeoWhitelistDomains extends ListRecords
{
    protected static string $resource = SeoWhitelistDomainResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
