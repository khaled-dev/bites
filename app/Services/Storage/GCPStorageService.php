<?php

namespace App\Services\Storage;

use App\Models\File;
use App\Services\Storage\Concerns\StorageContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Enums\Storage as StorageType;

class GCPStorageService extends StorageContract
{
    protected $disk = StorageType::GCP;

    public function upload(string $file, $string filename): array
    {
        $path = Storage::disk($this->disk)->put($filename, $file);

        if ($path === false) {
            throw new \Exception("GCP upload failed");
        }

        return [
            'success' => true,
            'storage' => StorageType::GCP,
            'path' => $path,
        ];
    }
}
