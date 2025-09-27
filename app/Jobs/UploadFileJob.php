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
use Illuminate\Support\Facades\Redis;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Storage\Concerns\StorageFactoryService;

class UploadFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];
    public FileService $fileService;

    public array $storageServices = [
        StorageEnum::R2->value,
        StorageEnum::GCP->value,
    ];

    public function __construct(
        public readonly File $file,
        public readonly string $redisKey,
    ) {
        $this->fileService = app(FileService::class);
    }

    public function handle(): void
    {
        foreach ($this->storageServices as $storage) {
            try {
                $uploadedFile = $this->uploadFile($storage);

                $this->file->update([
                    'storage' => $storage,
                    'status' => FileStatus::UPLOADED,
                    'path' => $uploadedFile['path'],
                ]);

                $this->fileService->publish($this->file->fresh());

                return;
            } catch (Exception $e) {
                Log::error("Upload failed for {$storage} (Attempt {$this->attempts()}): " . $e->getMessage());

                // If this was the last storage option and max attempts reached, mark as failed
                if ($storage === end($this->storageServices) && $this->attempts() >= $this->tries) {
                    Redis::del($this->redisKey);

                    $this->file->update([
                        'status' => FileStatus::FAILED,
                    ]);

                    throw new Exception('All storage services failed to upload the file after ' . $this->attempts() . ' attempts');
                }
            }
        }

        $this->release($this->backoff[$this->attempts() - 1] ?? 60);
    }

    private function uploadFile(string $storage): array
    {
        $fileData = json_decode(Redis::get($this->redisKey), true);
        if (!$fileData) {
            throw new Exception("Temporary file not found: {$this->redisKey}");
        }

        $storageService = StorageFactoryService::make($storage);
        $content        = base64_decode($fileData['content']);
        $uploadedFile   = $storageService->upload($content, $fileData['original_name']);

        Redis::del($this->redisKey);

        return $uploadedFile;
    }
}
