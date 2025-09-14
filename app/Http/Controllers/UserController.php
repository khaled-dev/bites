<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\UserResource;
use App\Http\Requests\IndexUserRequest;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(IndexUserRequest $request): JsonResponse
    {
        $userResourceCollection = UserResource::collection(
            $this->userService->searchBy(
                $request->input('name'),
                $request->input('dob'),
            )
        );

        return response()->json([
            'status' => 200,
            'message' => 'Users retrieved successfully',
            'data' => $userResourceCollection,
        ]);
    }
}
