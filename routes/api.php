<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('78688/v1')->group(function () {
    Route::apiResource('tasks', TaskController::class);
});
