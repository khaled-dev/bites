<?php

namespace App\Services\Storage;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Storage\Concerns\StorageContract;

class R2StorageService extends StorageContract
{
    public function upload(UploadedFile $file, $filename): array
    {
        $path = Storage::disk('r2')->putFileAs('', $file, $filename);

        if ($path === false) {
            throw new \Exception("R2 upload failed");
        }

        return [
            'success' => true,
            'storage' => 'r2',
            'path' => $path,
        ];
    }
}
