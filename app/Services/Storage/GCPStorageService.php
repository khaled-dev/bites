<?php

namespace App\Services\Storage;

use Exception;
use Illuminate\Support\Facades\Storage;
use App\Enums\Storage as StorageType;
use App\Services\Storage\Concerns\StorageContract;

class GCPStorageService extends StorageContract
{
    protected $disk = StorageType::GCP->value;

    public function upload(string $content, string $filename): array
    {
        $path = Storage::disk($this->disk)->put($filename, $content);

        if ($path === false) {
            throw new Exception("GCP upload failed");
        }

        return [
            'success' => true,
            'storage' => StorageType::GCP,
            'path' => $path,
        ];
    }
}
