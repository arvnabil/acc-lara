<?php

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Models\ProductImage;
use Throwable;

class MigrateProductImages extends Command
{
    protected $signature = 'catalog:images-to-storage {--dry-run : Only report what would be migrated}';

    protected $description = 'Migrate external product image URLs to the public storage disk';

    public function handle(): int
    {
        $migrated = 0;
        $skipped = 0;
        $failed = 0;

        ProductImage::query()
            ->with('product.brand')
            ->where(function ($query): void {
                $query->where('url', 'like', 'http://%')
                    ->orWhere('url', 'like', 'https://%');
            })
            ->orderBy('id')
            ->chunkById(50, function ($images) use (&$migrated, &$skipped, &$failed): void {
                foreach ($images as $image) {
                    $url = $image->url;
                    $directory = 'catalog/'.Str::slug(
                        $image->product?->brand?->slug ?? $image->product?->brand?->name ?? 'tanpa-brand'
                    ).'/'.Str::slug($image->product?->slug ?? $image->product?->name ?? 'produk');
                    $extension = $this->extensionFromUrl($url);
                    $stem = Str::slug(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_FILENAME)) ?: 'image';
                    $filename = ($image->sort_order + 1).'-'.$stem.'.'.$extension;
                    $path = trim($directory.'/'.$filename, '/');

                    if ($this->option('dry-run')) {
                        $this->line("[DRY RUN] {$url} -> {$path}");
                        $skipped++;
                        continue;
                    }

                    try {
                        if (Storage::disk('public')->exists($path)) {
                            $image->update(['url' => $path]);
                            $skipped++;
                            continue;
                        }

                        $response = Http::timeout(30)->retry(2, 500)->get($url);

                        if (! $response->successful() || ! $this->isImage($response)) {
                            $this->warn("Gagal mengambil image #{$image->id}: {$url}");
                            $failed++;
                            continue;
                        }

                        Storage::disk('public')->put($path, $response->body());
                        $image->update(['url' => $path]);
                        $migrated++;
                    } catch (Throwable $exception) {
                        $this->warn("Gagal image #{$image->id}: {$exception->getMessage()}");
                        $failed++;
                    }
                }
            });

        $this->info("Migrated: {$migrated}; skipped: {$skipped}; failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function extensionFromUrl(string $url): string
    {
        $extension = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg';
    }

    private function isImage(Response $response): bool
    {
        $mime = strtolower((string) $response->header('Content-Type'));

        return str_starts_with($mime, 'image/') && @getimagesizefromstring($response->body()) !== false;
    }
}
