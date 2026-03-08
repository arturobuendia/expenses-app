<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function index()
    {
        // Ordenamos: primero los no pagados (is_paid = 0), luego por fecha más reciente
        $loans = Loan::orderBy('is_paid', 'asc')
            ->orderByDesc('date')
            ->get();

        return Inertia::render('Loans/Index', [
            'loans' => $loans,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'debtor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
            'is_paid' => 'boolean',
        ]);

        Loan::create($validated);

        return redirect()->back()->with('success', 'Préstamo registrado.');
    }

    public function update(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'debtor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
            'is_paid' => 'boolean',
        ]);

        $loan->update($validated);

        return redirect()->back()->with('success', 'Préstamo actualizado.');
    }

    public function markAsPaid(Loan $loan)
    {
        $loan->update(['is_paid' => true]);

        return redirect()->back()->with('success', '¡Préstamo marcado como pagado!');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();

        return redirect()->back()->with('success', 'Registro eliminado.');
    }
}
