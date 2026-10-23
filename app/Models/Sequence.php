<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sequence extends Model
{
    protected $fillable = [
        'entity',
        'prefix',
        'format',
        'next_number',
        'reset_yearly',
        'last_reset_year',
    ];

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
            'reset_yearly' => 'boolean',
            'last_reset_year' => 'integer',
        ];
    }
}
