<?php

namespace App\Services;

use App\Models\File;
use App\Enums\FileStatus;
use App\Jobs\UploadFileJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Messaging\RabbitMqService;

readonly class FileService
{
    public function __construct(
        private RabbitMqService $publisher,
    ) {
    }

    public function upload(UploadedFile $uploadedFile): File
    {
        $fileRecord = $this->store($uploadedFile->getClientOriginalName());

        $tempPath = Storage::disk('local')->putFile('temp', $uploadedFile);
        UploadFileJob::dispatch($fileRecord, $tempPath);

        return $fileRecord;
    }

    public function store(string $filename): File
    {
        return File::create([
            'filename' => $filename,
            'status' => FileStatus::PENDING,
            'path' => null,
        ]);
    }

    public function publish(File $file): void
    {
        $this->publisher->publish([
            'event' => 'file.uploaded',
            'file_id' => $file->id,
            'filename' => $file->filename,
            'storage' => $file->storage,
        ]);
    }
}
