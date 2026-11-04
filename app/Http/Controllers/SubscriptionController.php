<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Models\Subscription;
use App\Services\SubscriptionBiller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index(SubscriptionBiller $biller)
    {
        // Materializa los cobros vencidos antes de listar.
        $biller->run();

        $subscriptions = Subscription::with(['card', 'category'])
            ->orderBy('next_billing_date', 'asc')
            ->get();

        // Calculamos las métricas separando las activas de las pausadas
        $activeSubs = $subscriptions->where('is_active', true);
        $monthlyCost = $activeSubs->sum(fn (Subscription $subscription) => $subscription->monthlyAmount());

        $totals = [
            'active_count' => $activeSubs->count(),
            'monthly_cost' => $monthlyCost,
            'annual_projection' => $monthlyCost * 12,
            'paused_count' => $subscriptions->where('is_active', false)->count(),
        ];

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions->values(), // values() reindexa el array para Vue
            'categories' => Category::active()->ofType('expense')->get(['id', 'name']),
            'cards' => Card::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'totals' => $totals,
        ]);
    }

    public function store(Request $request)
    {
        Subscription::create($this->validatedData($request));

        return redirect()->back()->with('success', 'Suscripción agregada correctamente.');
    }

    public function update(Request $request, Subscription $subscription)
    {
        $subscription->update($this->validatedData($request));

        return redirect()->back()->with('success', 'Suscripción actualizada.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->back()->with('success', 'Suscripción eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            // Sin categoría el gasto generado no podría existir.
            'category_id' => 'required|exists:categories,id',
            'card_id' => 'nullable|exists:cards,id',
            'next_billing_date' => 'required|date',
            'frequency' => 'required|in:monthly,annual',
            'is_active' => 'boolean',
        ]);
    }
}
