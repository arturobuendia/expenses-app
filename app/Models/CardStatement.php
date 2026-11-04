<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardStatement extends Model
{
    protected $fillable = [
        'card_id',
        'period_start',
        'period_end',
        'cut_date',
        'due_date',
    ];

    protected $hidden = ['total_charges'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'cut_date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * Cargos del ciclo. Los pagos nunca se asignan a un estado de cuenta.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function totalAmount(): float
    {
        return (float) ($this->total_charges ?? $this->charges()->sum('amount'));
    }
}
