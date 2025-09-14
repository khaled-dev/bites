<?php

namespace App\Enums;

enum FileStatus: string
{
    case PENDING = 'pending';
    case UPLOADED = 'uploaded';
    case FAILED = 'failed';
}
