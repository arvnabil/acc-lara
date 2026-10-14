<?php

use App\Support\Modules\Module;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Collection;

uses(Tests\TestCase::class);

test('module manager sorts modules by priority and dependency', function () {
    $manager = new ModuleManager();
    $enabled = $manager->enabled();

    expect($enabled)->toBeInstanceOf(Collection::class);

    $positions = $enabled->keys()->flip();
    foreach ($enabled as $module) {
        foreach ($module->requires as $dependency) {
            expect($positions[$dependency] ?? null)
                ->not->toBeNull("Missing enabled dependency {$dependency} for {$module->name}");
            expect($positions[$dependency])->toBeLessThan($positions[$module->name]);
        }
    }
});

test('module manager throws exception on missing dependency', function () {
    $manager = new class extends ModuleManager {
        public function all(): Collection
        {
            return collect([
                'BrokenModule' => new Module(
                    name: 'BrokenModule',
                    path: '/path',
                    enabled: true,
                    priority: 10,
                    requires: ['NonExistentModule'],
                    permissions: []
                ),
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
                'ModA' => new Module(
                    name: 'ModA',
                    path: '/pathA',
                    enabled: true,
                    priority: 10,
                    requires: ['ModB'],
                    permissions: []
                ),
                'ModB' => new Module(
                    name: 'ModB',
                    path: '/pathB',
                    enabled: true,
                    priority: 20,
                    requires: ['ModA'],
                    permissions: []
                ),
            ]);
        }
    };

    expect(fn () => $manager->enabled())->toThrow(RuntimeException::class, 'Dependensi melingkar');
});
