<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Loan;
use App\Models\Subscription;
use App\Models\Vault;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // 1. Totales del mes actual (Ingresos vs Egresos aislados)
        $totalExpenses = Expense::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        $totalIncomes = Income::whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->sum('amount');

        // Flujo Neto del Mes
        $netFlow = $totalIncomes - $totalExpenses;

        // 2. Tu Capital Total, Inversiones y Rendimientos
        // El saldo de cada bóveda se calcula a partir de sus movimientos.
        $vaults = Vault::withMovements()
            ->get()
            ->map(fn (Vault $vault) => $vault->withComputedBalance())
            ->sortByDesc('balance')
            ->values();

        $totalCapital = $vaults->sum('balance');
        $monthlyYield = $vaults->sum(fn (Vault $vault) => $vault->monthlyYield());

        // 3. Dinero en la calle (Préstamos pendientes)
        $pendingLoansAmount = Loan::where('is_paid', false)->sum('amount');

        // 4. Próximos gastos fijos (Suscripciones)
        $upcomingSubscriptions = Subscription::where('is_active', true)
            ->where('next_billing_date', '>=', $now->toDateString())
            ->orderBy('next_billing_date', 'asc')
            ->take(5)
            ->get();

        // 5. Vistazo rápido a los últimos gastos
        $latestExpenses = Expense::with('category')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'totals' => [
                'expenses' => (float) $totalExpenses,
                'incomes' => (float) $totalIncomes,
                'net_flow' => (float) $netFlow,
                'capital' => (float) $totalCapital,
                'monthly_yield' => (float) $monthlyYield,
                'pending_loans' => (float) $pendingLoansAmount,
            ],
            'vaults' => $vaults,
            'upcomingSubscriptions' => $upcomingSubscriptions,
            'latestExpenses' => $latestExpenses,
        ]);
    }
}
