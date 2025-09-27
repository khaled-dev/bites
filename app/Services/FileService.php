<?php

namespace App\Services;

use App\Models\File;
use App\Enums\FileStatus;
use App\Jobs\UploadFileJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Redis;
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
        $redisKey   = $this->storeTempFile($fileRecord, $uploadedFile);

        UploadFileJob::dispatch($fileRecord, $redisKey);

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

    private function storeTempFile(File $fileRecord, UploadedFile $uploadedFile): string
    {
        $redisKey = "temp_file:{$fileRecord->id}";
        $fileContent = base64_encode(
            file_get_contents($uploadedFile->getRealPath())
        );

        Redis::setex($redisKey, 3600, json_encode([
            'content' => $fileContent,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'original_name' => $uploadedFile->getClientOriginalName(),
        ]));

        return $redisKey;
    }
}
