<?php

namespace App\Http\Controllers;

use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VaultController extends Controller
{
    public function index()
    {
        // El saldo es calculado (initial_balance + ingresos - gastos), así que
        // precargamos los movimientos y ordenamos en memoria.
        $vaults = Vault::withMovements()
            ->get()
            ->map(fn (Vault $vault) => $vault->withComputedBalance())
            ->sortByDesc('balance')
            ->values()
            ->map(function (Vault $vault) {
                $vault->daily_yield = $vault->dailyYield();
                $vault->monthly_yield = $vault->monthlyYield();

                return $vault;
            });

        $totals = [
            'accounts' => $vaults->count(),
            'capital' => $vaults->sum('balance'),
            'daily_yield' => $vaults->sum('daily_yield'),
            'monthly_yield' => $vaults->sum('monthly_yield'),
        ];

        return Inertia::render('Vaults/Index', [
            'vaults' => $vaults,
            'totals' => $totals,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'initial_balance' => 'required|numeric|min:0',
            'annual_yield_rate' => 'nullable|numeric|min:0|max:100',
            'yield_cap' => 'nullable|numeric|min:0',
        ]);

        Vault::create($validated);

        return redirect()->back()->with('success', 'Bóveda creada correctamente.');
    }

    public function update(Request $request, Vault $vault)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'initial_balance' => 'required|numeric|min:0',
            'annual_yield_rate' => 'nullable|numeric|min:0|max:100',
            'yield_cap' => 'nullable|numeric|min:0',
        ]);

        $vault->update($validated);

        return redirect()->back()->with('success', 'Bóveda actualizada.');
    }

    public function destroy(Vault $vault)
    {
        $vault->delete();

        return redirect()->back()->with('success', 'Bóveda eliminada.');
    }
}
