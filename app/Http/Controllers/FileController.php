<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\File;
use App\Services\FileService;
use App\Http\Resources\FileResource;
use App\Http\Requests\StoreFileRequest;
use App\Services\Storage\Concerns\StorageFactoryService;

class FileController extends Controller
{
    public function __construct(private readonly FileService $fileService)
    {
    }

    public function store(StoreFileRequest $request)
    {
        $fileRecord = $this->fileService->upload(
            $request->file('file'),
        );

        return response()->json(['id' => $fileRecord->id], 201);
    }

    public function show(File $file)
    {
        return new FileResource($file);
    }

    public function download(File $file)
    {
        $filename = $file->filename;
        $storageService = StorageFactoryService::make($file->storage);

        try {
            $uploadedFile = $storageService->getFile($filename);
        } catch (Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getCode());
        }

        return response()->streamDownload(function () use ($uploadedFile) {
            echo $uploadedFile;
        }, $filename, [
            'Content-Disposition' => 'attachment; filename="' . basename($filename) . '"',
        ]);
    }
}
