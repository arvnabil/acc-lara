<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Content\Models\Banner;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('imports the hardcoded homepage banners idempotently', function (): void {
    $this->artisan('content:import-banners')
        ->expectsOutput('Import banner selesai. Total banner: 4.')
        ->assertSuccessful();

    expect(Banner::query()->count())->toBe(4);

    $this->artisan('content:import-banners')->assertSuccessful();

    expect(Banner::query()->count())->toBe(4)
        ->and(Banner::query()->where('image_url', '/banner/october-sale.png')->value('target_url'))
        ->toBe('/katalog?sort=sale');
});
