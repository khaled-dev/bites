<?php

namespace App\Models;

use App\Enums\FileStatus;
use App\Enums\Storage as StorageEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;

    protected $fillable = ['filename', 'storage', 'status'];

}
