<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Installment;
use App\Models\Loan;
use App\Models\Subscription;
use App\Models\Vault;
use App\Services\SubscriptionBiller;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(SubscriptionBiller $biller)
    {
        // Los cobros de suscripciones vencidas se materializan al entrar.
        $biller->run();

        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // 1. Totales del mes actual (Ingresos vs Egresos aislados)
        // Las transferencias no cuentan como gasto: son movimientos de saldo.
        // Una compra a MSI tampoco pesa completa en el mes en que se hizo: solo
        // la parcialidad que se factura.
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $cashExpenses = Expense::whereDate('date', '>=', $monthStart->toDateString())
            ->whereDate('date', '<=', $monthEnd->toDateString())
            ->where('is_transfer', false)
            ->where('installment_count', 1)
            ->sum('amount');

        $installmentsThisMonth = Installment::whereDate('cut_date', '>=', $monthStart->toDateString())
            ->whereDate('cut_date', '<=', $monthEnd->toDateString())
            ->sum('amount');

        $totalExpenses = $cashExpenses + $installmentsThisMonth;

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

        // 5. Vistazo rápido a los últimos gastos reales (sin transferencias)
        $latestExpenses = Expense::with('category')
            ->where('is_transfer', false)
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
