<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index()
    {
        // Traemos todas las suscripciones
        $subscriptions = Subscription::orderBy('next_billing_date', 'asc')->get();

        // Calculamos las métricas separando las activas de las pausadas
        $activeSubs = $subscriptions->where('is_active', true);
        $monthlyCost = $activeSubs->sum('amount');

        $totals = [
            'active_count' => $activeSubs->count(),
            'monthly_cost' => $monthlyCost,
            'annual_projection' => $monthlyCost * 12,
            'paused_count' => $subscriptions->where('is_active', false)->count(),
        ];

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions->values(), // values() reindexa el array para Vue
            'totals' => $totals,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'next_billing_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        Subscription::create($validated);

        return redirect()->back()->with('success', 'Suscripción agregada correctamente.');
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'next_billing_date' => 'required|date',
            'is_active' => 'boolean',
        ]);

        $subscription->update($validated);

        return redirect()->back()->with('success', 'Suscripción actualizada.');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return redirect()->back()->with('success', 'Suscripción eliminada.');
    }
}
