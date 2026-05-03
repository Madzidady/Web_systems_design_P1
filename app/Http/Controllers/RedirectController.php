<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RedirectController extends Controller
{
    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $shortLink = ShortLink::query()
            ->where('short_code', $code)
            ->first();

        if (!$shortLink) {
            throw new NotFoundHttpException('Short link not found.');
        }

        $shortLink->increment('click_count');

        return redirect()->away($shortLink->original_url, 302);
    }
}
