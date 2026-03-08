<?php

namespace App\Http\Controllers;

use App\Models\Vault;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VaultController extends Controller
{
    public function index()
    {
        // Traemos todas las bóvedas ordenadas por el saldo de mayor a menor
        $vaults = Vault::orderByDesc('balance')->get();

        return Inertia::render('Vaults/Index', [
            'vaults' => $vaults,
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
