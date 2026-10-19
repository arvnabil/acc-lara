<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\ProductImage;

#[Signature('catalog:clean-duplicate-images {--apply : Delete duplicate records instead of only previewing them}')]
#[Description('Find and optionally remove duplicate product image records')]
class CleanDuplicateProductImages extends Command
{
    public function handle(): int
    {
        $duplicates = ProductImage::query()
            ->select('product_id', 'url', DB::raw('COUNT(*) as duplicate_count'))
            ->whereNotNull('url')
            ->groupBy('product_id', 'url')
            ->having('duplicate_count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('Tidak ditemukan duplikat product image.');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($duplicates as $duplicate) {
            $images = ProductImage::query()
                ->where('product_id', $duplicate->product_id)
                ->where('url', $duplicate->url)
                ->orderBy('id')
                ->get();
            $keep = $images->first();
            $remove = $images->slice(1);

            $this->line(sprintf(
                'Product #%s: %s — simpan #%s, duplikat: %s',
                $duplicate->product_id,
                $duplicate->url,
                $keep?->id,
                $remove->pluck('id')->implode(', '),
            ));

            if ($this->option('apply')) {
                foreach ($remove as $image) {
                    $image->delete();
                    $deleted++;
                }
            }
        }

        if (! $this->option('apply')) {
            $this->warn('Mode preview: tidak ada data yang dihapus. Gunakan --apply untuk menghapus duplikat.');

            return self::SUCCESS;
        }

        $this->info("Berhasil menghapus {$deleted} record duplikat product image.");

        return self::SUCCESS;
    }
}
