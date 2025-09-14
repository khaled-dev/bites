<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use App\Enums\Storage as StorageType;
use Illuminate\Support\Facades\Storage;
use App\Services\Storage\Concerns\StorageContract;

class R2StorageService extends StorageContract
{
    public function upload(UploadedFile $file, $filename): array
    {
        $path = Storage::disk(StorageType::R2)->putFileAs('', $file, $filename);

        if ($path === false) {
            throw new \Exception("R2 upload failed");
        }

        return [
            'success' => true,
            'storage' => StorageType::R2,
            'path' => $path,
        ];
    }
}
