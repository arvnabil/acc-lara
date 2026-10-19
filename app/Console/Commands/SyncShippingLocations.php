<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Cart\Services\ShippingLocationService;

class SyncShippingLocations extends Command
{
    protected $signature = 'rajaongkir:sync-locations';

    protected $description = 'Synchronize RajaOngkir location data.';

    public function handle(ShippingLocationService $locations): int
    {
        $count = $locations->syncAll()->count();
        $this->info("Synchronized {$count} RajaOngkir locations.");

        return self::SUCCESS;
    }
}
