<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    public function index()
    {
        // Traemos todos los préstamos con sus abonos para calcular los totales
        // sobre el saldo pendiente, no sobre el monto original.
        $loans = Loan::with('payments')
            ->get()
            ->each(fn (Loan $loan) => $loan->withComputedPaymentTotals());

        // Pendiente = saldo restante mayor a cero; liquidado = saldo en cero.
        $pendingLoans = $loans->filter(fn (Loan $loan) => ! $loan->is_settled);
        $settledLoans = $loans->filter(fn (Loan $loan) => $loan->is_settled);

        $receivablePending = $pendingLoans->where('direction', 'lent');
        $payablePending = $pendingLoans->where('direction', 'borrowed');
        $receivableSettled = $settledLoans->where('direction', 'lent');
        $payableSettled = $settledLoans->where('direction', 'borrowed');

        $totals = [
            'receivable_pending_amount' => (float) $receivablePending->sum('remaining_amount'),
            'receivable_pending_count' => $receivablePending->count(),
            'payable_pending_amount' => (float) $payablePending->sum('remaining_amount'),
            'payable_pending_count' => $payablePending->count(),
            'receivable_settled_amount' => (float) $receivableSettled->sum('amount'),
            'receivable_settled_count' => $receivableSettled->count(),
            'payable_settled_amount' => (float) $payableSettled->sum('amount'),
            'payable_settled_count' => $payableSettled->count(),
        ];

        // Pendientes primero, después liquidados, y por fecha descendente.
        $loans = $loans
            ->sortBy(fn (Loan $loan) => $loan->is_settled ? 1 : 0)
            ->sortByDesc('date')
            ->values();

        return Inertia::render('Loans/Index', [
            'loans' => $loans,
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

    public function storePayment(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'note' => 'nullable|string|max:255',
        ]);

        $remaining = $loan->remainingAmount();

        if ((float) $validated['amount'] > $remaining) {
            return redirect()->back()
                ->withErrors(['amount' => 'El abono no puede exceder el saldo pendiente.'])
                ->withInput();
        }

        $loan->payments()->create($validated);

        return redirect()->back()->with('success', 'Abono registrado.');
    }

    public function destroyPayment(Loan $loan, LoanPayment $payment)
    {
        abort_if($payment->loan_id !== $loan->id, 404);

        $payment->delete();

        return redirect()->back()->with('success', 'Abono eliminado.');
    }

    public function destroy(Loan $loan)
    {
        $loan->delete();

        return redirect()->back()->with('success', 'Registro eliminado.');
    }
}
