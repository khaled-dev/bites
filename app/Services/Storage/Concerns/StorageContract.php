<?php

namespace App\Services\Storage\Concerns;

use App\Models\File;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

abstract class StorageContract
{
    abstract public function upload(UploadedFile $file, $filename);

    public function getOriginUrl(string $filename): string
    {
        return Storage::disk($this->disk)->url($filename);
    }

    public function getFile(string $filename): string
    {
        $disk = Storage::disk($this->disk);

        if (! $disk->exists($filename)) {
            throw new Exception('File Not Found', 404);
        }

        return $disk->get($filename);
    }
}
