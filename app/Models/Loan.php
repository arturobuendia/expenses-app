<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    /**
     * `direction` indica quién debe: `lent` cuando el usuario prestó,
     * `borrowed` cuando el usuario pidió prestado.
     */
    protected $fillable = [
        'counterparty_name',
        'direction',
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
