<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EchoController extends Controller
{
    public function echo(Request $request): JsonResponse
    {
        $message = $request->input('message', 'Hello from EchoController');

        return response()->json([
            'success' => true,
            'message' => $message,
            'method' => $request->method(),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }
}
