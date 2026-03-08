<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function index()
    {
        // Traemos las suscripciones ordenadas por la fecha de próximo cobro
        $subscriptions = Subscription::orderBy('next_billing_date', 'asc')->get();

        return Inertia::render('Subscriptions/Index', [
            'subscriptions' => $subscriptions,
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
