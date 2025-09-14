<?php

namespace App\Services\Storage\Concerns;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

abstract class StorageContract
{
    abstract public function upload(UploadedFile $file, $filename);

    public function getDownloadUrl(File $file): string
    {
        return Storage::disk($file->storage)->url($file->filename);
    }
}
