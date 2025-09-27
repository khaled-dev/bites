<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use App\Enums\FileStatus;
use App\Jobs\UploadFileJob;
use Illuminate\Http\UploadedFile;
use App\Enums\Storage as StorageEnum;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redis;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake(StorageEnum::R2->value);
        Storage::fake(StorageEnum::GCP->value);
        Redis::flushall();
    }

    public function test_can_upload_file(): void
    {
        Queue::fake();

        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->postJson('/api/v1/files', [
            'file' => $file
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id']);

        $fileRecord = File::find($response->json('id'));

        $this->assertNotNull($fileRecord);
        $this->assertEquals($file->getClientOriginalName(), $fileRecord->filename);
        $this->assertEquals(FileStatus::PENDING->value, $fileRecord->status);

        Queue::assertPushed(UploadFileJob::class, function ($job) use ($fileRecord) {
            return $job->file->id === $fileRecord->id;
        });

        // Assert file content is stored in Redis
        $redisKey = 'temp_file:' . $fileRecord->id;
        $this->assertTrue(Redis::exists($redisKey) > 0);
    }

    public function test_cannot_upload_invalid_file(): void
    {
        $response = $this->postJson('/api/v1/files', [
            'file' => 'not-a-file'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_can_view_file_details(): void
    {
        $file = File::factory()->uploaded()->create([
            'filename' => 'test.txt',
        ]);

        $response = $this->getJson("/api/v1/files/{$file->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'filename',
                    'upload_status',
                    'origin_url',
                    'download_url',
                    'created_at',
                    'updated_at'
                ]
            ])
            ->assertJson([
                'data' => [
                    'filename' => 'test.txt',
                    'upload_status' => FileStatus::UPLOADED->value,
                ]
            ]);
    }

    public function test_cannot_view_nonexistent_file(): void
    {
        $response = $this->getJson("/api/v1/files/999");
        $response->assertNotFound();
    }

    public function test_job_properly_handles_file_upload(): void
    {
        Storage::fake(StorageEnum::R2->value);

        $file = File::factory()->create([
            'filename' => 'test.txt',
            'status' => FileStatus::PENDING
        ]);

        $redisKey = 'temp_file:' . $file->id;

        // Store file content in Redis with the expected format
        $fileData = json_encode([
            'content' => base64_encode('test file content'),
            'mime_type' => 'text/plain',
            'original_name' => 'test.txt'
        ]);
        Redis::setex($redisKey, 3600, $fileData);

        $job = new UploadFileJob($file, $redisKey);
        $job->handle();

        $file->refresh();

        $this->assertEquals(FileStatus::UPLOADED->value, $file->status);
        $this->assertEquals(StorageEnum::R2->value, $file->storage);

        // Assert file content is removed from Redis
        $this->assertFalse(Redis::exists($redisKey) > 0);
    }

    public function test_job_marks_file_as_failed_when_storage_fails(): void
    {
        $file = File::factory()->create([
            'filename' => 'test.txt',
            'status' => FileStatus::PENDING->value
        ]);

        $redisKey = 'temp_file:' . $file->id;

        // Store file content in Redis with the expected format
        $fileData = json_encode([
            'content' => base64_encode('test file content'),
            'mime_type' => 'text/plain',
            'original_name' => 'test.txt'
        ]);
        Redis::setex($redisKey, 3600, $fileData);

        $job = new UploadFileJob($file, $redisKey);
        $job->tries = 1;
        $job->storageServices = [
            'no_storage',
        ];

        try {
            $job->handle();
        } catch (\Exception $e) {
        }

        $file->refresh();

        $this->assertEquals(FileStatus::FAILED->value, $file->status);
        $this->assertNull($file->storage);

        // Assert file content is removed from Redis even on failure
        $this->assertFalse(Redis::exists($redisKey) > 0);
    }
}
