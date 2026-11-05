<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Installment extends Model
{
    protected $fillable = [
        'expense_id',
        'card_id',
        'number',
        'amount',
        'cut_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'cut_date' => 'date',
            'number' => 'integer',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
