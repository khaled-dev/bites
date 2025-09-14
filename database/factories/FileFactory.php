<?php

namespace Database\Factories;

use App\Models\File;
use App\Enums\FileStatus;
use App\Enums\Storage as StorageEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'filename' => $this->faker->uuid() . '.txt',
            'status' => FileStatus::PENDING->value,
            'storage' => null,
        ];
    }

    public function uploaded(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => FileStatus::UPLOADED->value,
                'storage' => StorageEnum::R2->value,
            ];
        });
    }
}
