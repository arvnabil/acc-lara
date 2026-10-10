<?php

namespace App\Support\Modules;

use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Pengganti nwidart/laravel-modules: scan folder, urutkan berdasarkan
 * prioritas + dependensi (requires), lalu serahkan ke ModuleServiceProvider.
 */
class ModuleManager
{
    /** @var Collection<string, Module>|null */
    private ?Collection $modules = null;

    /** @return Collection<string, Module> semua modul (termasuk nonaktif) */
    public function all(): Collection
    {
        return $this->modules ??= $this->load();
    }

    /** @return Collection<string, Module> hanya yang aktif, sudah terurut dependensi */
    public function enabled(): Collection
    {
        return $this->sortByDependency(
            $this->all()->filter(fn (Module $m) => $m->enabled)
        );
    }

    public function has(string $name): bool
    {
        return $this->all()->has($name) && $this->all()[$name]->enabled;
    }

    private function load(): Collection
    {
        $cache = base_path('bootstrap/cache/modules.php');
        if (config('modules.cache') && is_file($cache)) {
            return collect(require $cache)->map(fn (array $a) => new Module(...$a));
        }

        $base = config('modules.path');
        if (! is_dir($base)) {
            return collect();
        }

        return collect(glob($base.'/*', GLOB_ONLYDIR) ?: [])
            ->map(fn (string $dir) => Module::fromPath($dir))
            ->filter()
            ->keyBy(fn (Module $m) => $m->name)
            ->sortBy(fn (Module $m) => $m->priority);
    }

    private function sortByDependency(Collection $modules): Collection
    {
        $sorted = [];
        $visiting = [];

        $visit = function (Module $m) use (&$visit, &$sorted, &$visiting, $modules) {
            if (isset($sorted[$m->name])) {
                return;
            }
            if (isset($visiting[$m->name])) {
                throw new RuntimeException("Dependensi melingkar pada modul [{$m->name}].");
            }
            $visiting[$m->name] = true;

            foreach ($m->requires as $dep) {
                if (! $modules->has($dep)) {
                    throw new RuntimeException("Modul [{$m->name}] membutuhkan [{$dep}] yang tidak ada atau nonaktif.");
                }
                $visit($modules[$dep]);
            }

            unset($visiting[$m->name]);
            $sorted[$m->name] = $m;
        };

        $modules->each($visit);

        return collect($sorted);
    }
}
