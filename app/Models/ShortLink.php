<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    protected $fillable = [
        'original_url',
        'short_code',
        'click_count',
        'album_number',
    ];
}