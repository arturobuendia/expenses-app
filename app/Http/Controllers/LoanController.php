<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function index()
    {
        // Traemos todos los préstamos para calcular los totales
        $loans = Loan::orderBy('is_paid', 'asc')
            ->orderByDesc('date')
            ->get();

        // Cada registro es una cuenta por cobrar (`lent`) o por pagar
        // (`borrowed`); se separan para no mezclar ambas direcciones.
        $pendingLoans = $loans->where('is_paid', false);
        $paidLoans = $loans->where('is_paid', true);

        $receivablePending = $pendingLoans->where('direction', 'lent');
        $payablePending = $pendingLoans->where('direction', 'borrowed');
        $receivableSettled = $paidLoans->where('direction', 'lent');
        $payableSettled = $paidLoans->where('direction', 'borrowed');

        $totals = [
            'receivable_pending_amount' => $receivablePending->sum('amount'),
            'receivable_pending_count' => $receivablePending->count(),
            'payable_pending_amount' => $payablePending->sum('amount'),
            'payable_pending_count' => $payablePending->count(),
            'receivable_settled_amount' => $receivableSettled->sum('amount'),
            'receivable_settled_count' => $receivableSettled->count(),
            'payable_settled_amount' => $payableSettled->sum('amount'),
            'payable_settled_count' => $payableSettled->count(),
        ];

        return Inertia::render('Loans/Index', [
            'loans' => $loans->values(),
            'totals' => $totals,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'counterparty_name' => 'required|string|max:255',
            'direction' => 'required|in:lent,borrowed',
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
            'counterparty_name' => 'required|string|max:255',
            'direction' => 'required|in:lent,borrowed',
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
