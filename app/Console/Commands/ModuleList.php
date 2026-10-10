<?php

namespace App\Console\Commands;

use App\Support\Modules\ModuleManager;
use Illuminate\Console\Command;

class ModuleList extends Command
{
    protected $signature = 'module:list';
    protected $description = 'Tampilkan semua modul dan statusnya';

    public function handle(ModuleManager $modules): int
    {
        $this->table(
            ['Modul', 'Status', 'Prioritas', 'Requires'],
            $modules->all()->map(fn ($m) => [
                $m->name, $m->enabled ? 'aktif' : 'nonaktif', $m->priority, implode(', ', $m->requires),
            ])->values()
        );

        return self::SUCCESS;
    }
}
