<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'level',
        'message',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];
}
