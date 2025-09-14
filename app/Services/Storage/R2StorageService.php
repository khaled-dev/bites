<?php

namespace App\Services\Storage;

use Exception;
use Illuminate\Http\UploadedFile;
use App\Enums\Storage as StorageType;
use Illuminate\Support\Facades\Storage;
use App\Services\Storage\Concerns\StorageContract;

class R2StorageService extends StorageContract
{
    protected $disk = StorageType::R2->value;

    public function upload(string $file, string $filename): array
    {
        $path = Storage::disk($this->disk)->put($filename, $file);

        if ($path === false) {
            throw new Exception('R2 upload failed');
        }

        return [
            'success' => true,
            'storage' => StorageType::R2,
            'path' => $path,
        ];
    }
}

