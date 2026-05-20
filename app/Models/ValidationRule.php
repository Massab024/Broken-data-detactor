<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValidationRule extends Model
{
    protected $fillable = [
        'rule_key',
        'name',
        'description',
        'severity',
        'is_enabled',
        'config',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
    ];
}
