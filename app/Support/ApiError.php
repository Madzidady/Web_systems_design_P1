<?php

namespace App\Support;

use Illuminate\Http\Request;

class ApiError
{
    public static function make(Request $request, int $status, string $message)
    {
        return response()->json([
            'error' => [
                'status' => $status,
                'message' => $message,
                'path' => $request->getRequestUri(),
            ],
        ], $status);
    }
}
