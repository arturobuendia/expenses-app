<?php

namespace App\Http\Controllers;

use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VaultController extends Controller
{
    public function index()
    {
        // Traemos las bóvedas y calculamos sus rendimientos al vuelo
        $vaults = Vault::orderByDesc('balance')->get()->map(function ($vault) {
            $dailyYield = 0;
            $monthlyYield = 0;

            if ($vault->annual_yield_rate > 0) {
                // Si hay tope y el saldo lo supera, calculamos solo sobre el tope
                $effectiveBalance = ($vault->yield_cap && $vault->balance > $vault->yield_cap)
                    ? $vault->yield_cap
                    : $vault->balance;

                // Cálculo de rendimientos
                $annualYield = $effectiveBalance * ($vault->annual_yield_rate / 100);
                $dailyYield = $annualYield / 365; // Estándar de 365 días
                $monthlyYield = $annualYield / 12;
            }

            // Mutamos el objeto para agregarle estas propiedades virtuales
            $vault->daily_yield = $dailyYield;
            $vault->monthly_yield = $monthlyYield;

            return $vault;
        });

        // Calculamos los totales generales para las tarjetas
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
            'balance' => 'required|numeric|min:0',
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
            'balance' => 'required|numeric|min:0',
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
