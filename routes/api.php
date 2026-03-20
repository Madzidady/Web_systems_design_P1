<?php

declare(strict_types=1);

use App\Http\Controllers\EchoController;
use Illuminate\Support\Facades\Route;

Route::get('/echo', [EchoController::class, 'echo']);
Route::post('/echo', [EchoController::class, 'echo']);
