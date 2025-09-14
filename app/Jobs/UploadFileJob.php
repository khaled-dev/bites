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

    public $tries = 3;

    public $backoff = [10, 30, 60];

    public function __construct(
        private readonly File $file,
        private readonly string $tempPath,
    ) {}

    public function handle(): void
    {
        $storageServices = [
            StorageEnum::R2->value,
            StorageEnum::GCP->value,
        ];

        $fileService = app(FileService::class);

        foreach ($storageServices as $storage) {
            try {
                $storageService = StorageFactoryService::make($storage);
                if (!Storage::disk('local')->exists($this->tempPath)) {
                    throw new Exception("Temporary file not found: {$this->tempPath}");
                }

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
                Log::error("Upload failed for {$storage} (Attempt {$this->attempts()}): " . $e->getMessage());

                // rollback temp files & update status to failed
                if ($storage === end($storageServices) && $this->attempts() >= $this->tries) {

                    Storage::disk('local')->delete($this->tempPath);

                    $this->file->update([
                        'status' => FileStatus::FAILED,
                    ]);

                    throw new Exception('All storage services failed to upload the file after ' . $this->attempts() . ' attempts');
                }
            }
        }

        $this->release($this->backoff[$this->attempts() - 1] ?? 60);
    }
}
