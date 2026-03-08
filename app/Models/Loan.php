<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'debtor_name',
        'amount',
        'description',
        'date',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_paid' => 'boolean',
        ];
    }
}
