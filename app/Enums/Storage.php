<?php

namespace App\Enums;

enum Storage: string
{
    case R2 = 'r2';
    case GCP = 'gcp';
}
