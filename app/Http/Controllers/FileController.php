<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use App\Http\Requests\StoreFileRequest;
use App\Services\Storage\GCPStorageService;

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
        //TODO:
        // remove service
        // map service by storage-type
        $storageService = new GCPStorageService();
        $downloadUrl = $storageService->getDownloadUrl($file);

        // use resource
        return response()->json([
            'filename' => $file->filename,
            'created_at' => $file->created_at,
            'updated_at' => $file->updated_at,
            'download_url' => $downloadUrl,
        ]);
    }
}
