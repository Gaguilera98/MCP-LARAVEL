<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceConnection extends Model
{
    protected $fillable = [
        'service',
        'base_url',
        'token',
    ];

    protected $hidden = [
        'token',
    ];

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
        ];
    }
}
