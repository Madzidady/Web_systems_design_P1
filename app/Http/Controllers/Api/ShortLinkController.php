<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use App\Support\Base62;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ShortLinkController extends Controller
{
    public function index(): JsonResponse
    {
        $links = Cache::remember('short-links.index', 60, function () {
            return ShortLink::query()
                ->latest()
                ->get();
        });

        return response()->json([
            'data' => $links,
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'original_url' => ['required', 'url', 'max:2048'],
            'album_number' => ['required', 'string', 'max:50'],
        ]);

        $shortLink = ShortLink::create([
            'original_url' => $validated['original_url'],
            'album_number' => $validated['album_number'],
        ]);

        $shortLink->short_code = Base62::encode($shortLink->id);
        $shortLink->save();

        Cache::forget('short-links.index');

        return response()->json([
            'data' => $shortLink,
            'short_url' => url('/r/' . $shortLink->short_code),
        ], 201);
    }

    public function show(ShortLink $shortLink): JsonResponse
    {
        return response()->json([
            'data' => $shortLink,
        ], 200);
    }
}
