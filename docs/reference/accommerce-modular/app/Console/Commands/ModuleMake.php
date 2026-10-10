<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModuleMake extends Command
{
    protected $signature = 'module:make {name : Nama modul, PascalCase (mis. Promo)}';
    protected $description = 'Buat kerangka modul native baru';

    public function handle(Filesystem $fs): int
    {
        $name = Str::studly($this->argument('name'));
        $root = base_path("Modules/{$name}");

        if ($fs->isDirectory($root)) {
            $this->error("Modul {$name} sudah ada.");

            return self::FAILURE;
        }

        foreach (['Database/Migrations', 'Database/Seeders', 'Models', 'Http/Controllers', 'Routes',
            'Services', 'Events', 'Listeners', 'Filament/Resources', 'resources/js/Pages'] as $dir) {
            $fs->makeDirectory("{$root}/{$dir}", 0755, true);
        }

        $snake = Str::snake($name);
        $fs->put("{$root}/module.json", json_encode([
            'name' => $name, 'description' => '', 'enabled' => true, 'priority' => 100,
            'requires' => [], 'permissions' => ["view_any_{$snake}", "create_{$snake}", "update_{$snake}", "delete_{$snake}"],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $fs->put("{$root}/{$name}ServiceProvider.php", <<<PHP
<?php

namespace Modules\\{$name};

use App\\Support\\Modules\\BaseModuleServiceProvider;

class {$name}ServiceProvider extends BaseModuleServiceProvider
{
    protected string \$moduleName = '{$name}';
}

PHP);

        $fs->put("{$root}/Routes/web.php", "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n// Route modul {$name}\n");

        $this->info("Modul {$name} dibuat di Modules/{$name}. Jalankan: composer dump-autoload");

        return self::SUCCESS;
    }
}
