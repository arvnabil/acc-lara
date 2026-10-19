<?php

namespace Modules\SEO\Filament\Resources\SeoWhitelistDomainResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\SEO\Filament\Resources\SeoWhitelistDomainResource;

class EditSeoWhitelistDomain extends EditRecord
{
    protected static string $resource = SeoWhitelistDomainResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
