<?php

namespace Modules\SEO\Services\Seo;

use Modules\SEO\Models\SeoSetting;

class SeoManager
{
    public function graph(array $pageSchema): array
    {
        $settings = SeoSetting::query()->first();
        $siteName = $settings?->default_title ?: config('app.name');
        $baseUrl = rtrim(config('app.url'), '/');
        $logo = $settings?->default_og_image ? asset('storage/'.$settings->default_og_image) : null;

        return [
            [
                '@type' => 'Organization',
                '@id' => $baseUrl.'#organization',
                'name' => $siteName,
                'url' => $baseUrl,
                'logo' => $logo,
            ],
            [
                '@type' => 'WebSite',
                '@id' => $baseUrl.'#website',
                'url' => $baseUrl,
                'name' => $siteName,
                'publisher' => ['@id' => $baseUrl.'#organization'],
                'inLanguage' => 'id-ID',
            ],
            $pageSchema,
        ];
    }
}
