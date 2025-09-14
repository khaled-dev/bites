<?php

namespace Tests\Unit\Services\Storage;

use Tests\TestCase;
use App\Enums\Storage;
use App\Services\Storage\R2StorageService;
use App\Services\Storage\GCPStorageService;
use InvalidArgumentException;
use App\Services\Storage\Concerns\StorageFactoryService;

class StorageFactoryServiceTest extends TestCase
{
    public function test_it_creates_r2_storage_service_when_r2_is_specified(): void
    {
        $service = StorageFactoryService::make(Storage::R2->value);

        $this->assertInstanceOf(R2StorageService::class, $service);
    }

    public function test_it_creates_gcp_storage_service_when_gcp_is_specified(): void
    {
        $service = StorageFactoryService::make(Storage::GCP->value);

        $this->assertInstanceOf(GCPStorageService::class, $service);
    }

    public function test_it_throws_exception_for_invalid_storage_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported storage type: invalid');

        StorageFactoryService::make('invalid');
    }
}
