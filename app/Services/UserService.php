<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserService
{
    public function searchBy(
        ?string $name = null,
        ?string $dob = null,
    ): Collection
    {
        $userQuery = User::query();

        if ($name) {
            $userQuery->whereNameLike($name);
        }

        if ($dob) {
            $userQuery->whereDob($dob);
        }

        return $userQuery->get();
    }



}
