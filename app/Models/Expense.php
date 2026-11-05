<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    protected $fillable = [
        'category_id',
        'vault_id',
        'card_id',
        'card_statement_id',
        'subscription_id',
        'amount',
        'description',
        'date',
        'is_transfer',
        'installment_count',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_transfer' => 'boolean',
            'installment_count' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function statement(): BelongsTo
    {
        return $this->belongsTo(CardStatement::class, 'card_statement_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('number');
    }

    /**
     * Genera el calendario de parcialidades de una compra a MSI.
     *
     * El total se reparte en centavos para que la suma de las parcialidades
     * sea exactamente el monto de la compra; el residuo cae en la última.
     */
    public function syncInstallments(): void
    {
        $this->installments()->delete();

        $months = (int) $this->installment_count;

        if ($months <= 1 || ! $this->card_id) {
            return;
        }

        $card = Card::find($this->card_id);

        if (! $card) {
            return;
        }

        $totalCents = (int) round((float) $this->amount * 100);
        $baseCents = intdiv($totalCents, $months);

        $cursor = $card->cycleFor($this->date)['cut_date']->startOfMonth();

        for ($number = 1; $number <= $months; $number++) {
            $amountCents = $number === $months
                ? $totalCents - $baseCents * ($months - 1)
                : $baseCents;

            $this->installments()->create([
                'card_id' => $card->id,
                'number' => $number,
                'amount' => $amountCents / 100,
                'cut_date' => $card->cutDateFor($cursor->year, $cursor->month)->toDateString(),
            ]);

            $cursor = $cursor->addMonthNoOverflow();
        }
    }
}
