<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResourceCollection;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use App\Http\Requests\IndexUserRequest;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(IndexUserRequest $request): UserResourceCollection
    {
        $searchResult = $this->userService->searchBy(
            $request->input('name'),
            $request->input('dob'),
            $request->input('per_page', 10),
        );

        return (new UserResourceCollection($searchResult));
    }
}
