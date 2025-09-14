<?php

namespace App\Services\Storage;

use App\Models\File;
use App\Services\Storage\Concerns\StorageContract;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GCPStorageService extends StorageContract
{
    public function upload(UploadedFile $file, $filename): array
    {
        $path = Storage::disk('gcp')->putFileAs('', $file, $filename);

        if ($path === false) {
            throw new \Exception("GCP upload failed");
        }

        return [
            'success' => true,
            'storage' => 'gcp',
            'path' => $path,
        ];
    }
}
