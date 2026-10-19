<?php

namespace Modules\SEO\Filament\Pages;

use Filament\Pages\Page;
use Modules\SEO\Filament\Resources\SeoMetaResource;

class SeoAudit extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-magnifying-glass-circle';
    protected static string | \UnitEnum | null $navigationGroup = 'Seo Management';
    protected static ?string $navigationLabel = 'SEO Audit';
    protected static ?string $title = 'SEO Audit';
    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;
    protected string $view = 'filament.seo.audit';

    public function mount(): void
    {
        $this->redirect(SeoMetaResource::getUrl('index'), navigate: true);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_seo_audit') ?? false;
    }
}
