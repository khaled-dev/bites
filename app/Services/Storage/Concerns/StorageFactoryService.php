<?php

namespace App\Services\Storage\Concerns;

use App\Enums\Storage;
use App\Services\Storage\GCPStorageService;
use App\Services\Storage\R2StorageService;

class StorageFactoryService
{
    public static function make(string $storage): GCPStorageService|R2StorageService
    {
        return match ($storage) {
            Storage::GCP->value => new GCPStorageService(),
            Storage::R2->value => new R2StorageService(),
            default => throw new \InvalidArgumentException("Unsupported storage type: $storage"),
        };
    }
}
