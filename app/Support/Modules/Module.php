<?php

namespace App\Support\Modules;

/**
 * Value object satu modul, dibaca dari Modules/{Nama}/module.json.
 */
final class Module
{
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly bool $enabled,
        public readonly int $priority,
        public readonly array $requires,
        public readonly array $permissions,
        public readonly string $description = '',
    ) {}

    public static function fromPath(string $path): ?self
    {
        $file = $path.'/module.json';
        if (! is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);

        return new self(
            name: $data['name'] ?? basename($path),
            path: $path,
            enabled: (bool) ($data['enabled'] ?? true),
            priority: (int) ($data['priority'] ?? 100),
            requires: $data['requires'] ?? [],
            permissions: $data['permissions'] ?? [],
            description: $data['description'] ?? '',
        );
    }

    public function namespace(): string
    {
        return config('modules.namespace').'\\'.$this->name;
    }

    public function providerClass(): string
    {
        return $this->namespace().'\\'.$this->name.'ServiceProvider';
    }

    public function filamentResourcePath(): string
    {
        return $this->path.'/Filament/Resources';
    }

    public function filamentPagePath(): string
    {
        return $this->path.'/Filament/Pages';
    }
}
