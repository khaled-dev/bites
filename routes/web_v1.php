<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/files/{file}/download', [App\Http\Controllers\FileController::class, 'download'])->name('files.download');
});


