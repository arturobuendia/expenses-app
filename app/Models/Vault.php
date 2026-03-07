<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vault extends Model
{
    protected $fillable = [
        'name',
        'balance',
        'annual_yield_rate',
        'yield_cap',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'annual_yield_rate' => 'decimal:2',
            'yield_cap' => 'decimal:2',
        ];
    }
}