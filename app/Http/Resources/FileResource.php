<?php

namespace App\Http\Resources;

use App\Services\Storage\Concerns\StorageFactoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $data = [
            'filename'      => $this->filename,
            'upload_status' => $this->status,
        ];

        if ($this->storage !== null) {
            $storageService = StorageFactoryService::make($this->storage);

            $data += [
                'origin_url'    => $storageService->getOriginUrl($this->filename),
                'download_url'  => route('files.download', ['file' => $this->id]),
            ];
        }

        return $data + [
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
