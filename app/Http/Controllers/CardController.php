<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardStatement;
use App\Models\Expense;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CardController extends Controller
{
    public function index()
    {
        $cards = Card::withTotals()
            ->orderBy('name')
            ->get()
            ->map(function (Card $card) {
                $card->withComputedTotals();

                $cycle = $card->cycleFor(Carbon::now());
                $card->cycle = [
                    'period_start' => $cycle['period_start']->toDateString(),
                    'period_end' => $cycle['period_end']->toDateString(),
                    'cut_date' => $cycle['cut_date']->toDateString(),
                    'due_date' => $cycle['due_date']->toDateString(),
                ];

                $summaries = $card->settledStatements()->take(-6)->values();
                $card->statement_summaries = $summaries;

                $nextDue = $summaries->first(fn ($statement) => ! $statement->is_settled);
                $card->next_due = $nextDue?->due_date?->toDateString();

                return $card;
            });

        $totals = [
            'cards' => $cards->count(),
            'credit_limit' => $cards->sum('credit_limit'),
            'debt' => $cards->sum('debt'),
            'available_credit' => $cards->sum('available_credit'),
        ];

        return Inertia::render('Cards/Index', [
            'cards' => $cards,
            'totals' => $totals,
        ]);
    }

    public function show(Card $card)
    {
        $card->withComputedTotals();

        $statements = $card->settledStatements();

        // Cargos (compras normales) agrupados por su estado de cuenta.
        $charges = Expense::with('category')
            ->whereIn('card_statement_id', $statements->pluck('id'))
            ->where('is_transfer', false)
            ->orderBy('date')
            ->get()
            ->groupBy('card_statement_id');

        // Parcialidades MSI agrupadas por fecha de corte.
        $installments = Installment::with('expense')
            ->where('card_id', $card->id)
            ->get()
            ->groupBy(fn (Installment $installment) => $installment->cut_date->toDateString());

        $statements = $statements
            ->map(function (CardStatement $statement) use ($charges, $installments) {
                $statement->total = round((float) $statement->paid_amount + (float) $statement->pending_amount, 2);
                $statement->setRelation('charges', $charges->get($statement->id, collect()));
                $statement->setRelation('installments', $installments->get($statement->cut_date->toDateString(), collect()));

                return $statement;
            })
            ->reverse()
            ->values();

        return Inertia::render('Cards/Show', [
            'card' => $card,
            'statements' => $statements,
        ]);
    }

    public function store(Request $request)
    {
        Card::create($this->validatedData($request));

        return redirect()->back()->with('success', 'Tarjeta creada correctamente.');
    }

    public function update(Request $request, Card $card)
    {
        $card->update($this->validatedData($request));

        return redirect()->back()->with('success', 'Tarjeta actualizada.');
    }

    public function destroy(Card $card)
    {
        $card->delete();

        return redirect()->back()->with('success', 'Tarjeta eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'credit_limit' => 'required|numeric|min:0',
            'cut_off_day' => 'required|integer|min:1|max:31',
            'payment_day' => 'required|integer|min:1|max:31',
            'is_active' => 'boolean',
        ]);
    }
}
