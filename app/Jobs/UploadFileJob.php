<?php

namespace App\Jobs;

use App\Enums\FileStatus;
use App\Services\FileService;
use Exception;
use App\Models\File;
use Illuminate\Bus\Queueable;
use App\Services\Storage\R2StorageService;
use App\Services\Storage\GCPStorageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly File $file,
        private readonly string $tempPath,
    ) {}

    public function handle(): void
    {
        $uploadedFile = new UploadedFile(
            Storage::disk('local')->path($this->tempPath),
            $this->file->filename
        );

        $storageServices = [
            new R2StorageService(),
            new GCPStorageService()
        ];

        $fileService = app(FileService::class);

        foreach ($storageServices as $storageService) {
            try {
                $result = $storageService->upload($uploadedFile, $this->file->filename);

                $this->file->update([
                    'storage' => $result['storage'],
                    'status' => FileStatus::UPLOADED,
                ]);

                $fileService->publish($this->file->fresh());

                // Clean up temp file
                Storage::disk('local')->delete($this->tempPath);
                return;
            } catch (\Exception $e) {
                Log::error("Upload failed for " . get_class($storageService) . ": " . $e->getMessage());
            }
        }

        Storage::disk('local')->delete($this->tempPath);

        $this->file->update([
            'status' => FileStatus::FAILED,
        ]);

        throw new Exception('All storage services failed to upload the file');
    }}
