<?php

use App\Support\Modules\Module;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Collection;

test('module manager sorts modules by priority and dependency', function () {
    $manager = new ModuleManager();
    $enabled = $manager->enabled();

    expect($enabled)->toBeInstanceOf(Collection::class);
    expect($enabled->keys()->toArray())->toEqual(['Accounts', 'Catalog']);
});

test('module manager throws exception on missing dependency', function () {
    $manager = new class extends ModuleManager {
        public function all(): Collection
        {
            return collect([
                'BrokenModule' => new Module('BrokenModule', 'App\\Broken', '/path', priority: 10, enabled: true, requires: ['NonExistentModule']),
            ]);
        }
    };

    expect(fn () => $manager->enabled())->toThrow(RuntimeException::class, 'NonExistentModule');
});

test('module manager throws exception on circular dependency', function () {
    $manager = new class extends ModuleManager {
        public function all(): Collection
        {
            return collect([
                'ModA' => new Module('ModA', 'App\\ModA', '/pathA', priority: 10, enabled: true, requires: ['ModB']),
                'ModB' => new Module('ModB', 'App\\ModB', '/pathB', priority: 20, enabled: true, requires: ['ModA']),
            ]);
        }
    };

    expect(fn () => $manager->enabled())->toThrow(RuntimeException::class, 'Dependensi melingkar');
});
