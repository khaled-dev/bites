<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;

class UserService
{
    public function searchBy(
        ?string $name = null,
        ?string $dob = null,
        int $perPage = 10,
    ): Paginator
    {
        $userQuery = User::query();

        if ($name) {
            $userQuery->whereNameLike($name);
        }

        if ($dob) {
            $userQuery->whereDob($dob);
        }

        return $userQuery
            ->orderByDesc('id')
            ->simplePaginate($perPage);
    }
}
