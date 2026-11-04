<?php

namespace App\Http\Controllers;

use App\Models\Card;
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
