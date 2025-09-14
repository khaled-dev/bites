<?php

namespace App\Jobs;

use Exception;
use App\Models\File;
use App\Enums\FileStatus;
use Illuminate\Bus\Queueable;
use App\Services\FileService;
use Illuminate\Support\Facades\Log;
use App\Enums\Storage as StorageEnum;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Storage\Concerns\StorageFactoryService;

class UploadFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly File $file,
        private readonly string $tempPath,
    ) {}

    public function handle(): void
    {
        $storageServices = [
            StorageEnum::R2,
            StorageEnum::GCP,
        ];

        $fileService = app(FileService::class);

        foreach ($storageServices as $storage) {
            try {
                $storageService = StorageFactoryService::make($storage);
                $fileContents = Storage::disk('local')->get($this->tempPath);
                $result = $storageService->upload($fileContents, $this->file->filename);

                $this->file->update([
                    'storage' => $storage,
                    'status' => FileStatus::UPLOADED,
                    'path' => $result['path'] ?? $this->file->filename,
                ]);

                $fileService->publish($this->file->fresh());

                Storage::disk('local')->delete($this->tempPath);
                return;
            } catch (Exception $e) {
                Log::error("Upload failed for {$storage}: " . $e->getMessage());
            }
        }

        Storage::disk('local')->delete($this->tempPath);

        $this->file->update([
            'status' => FileStatus::FAILED,
        ]);

        throw new Exception('All storage services failed to upload the file');
    }
}
