<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Vault;
use App\Models\Subscription;
use App\Models\Loan;
use Carbon\Carbon;

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
        $vaults = Vault::orderByDesc('balance')->get();
        $totalCapital = $vaults->sum('balance');

        $monthlyYield = $vaults->sum(function ($vault) {
            if ($vault->annual_yield_rate > 0) {
                $effectiveBalance = ($vault->yield_cap && $vault->balance > $vault->yield_cap)
                    ? $vault->yield_cap
                    : $vault->balance;
                return ($effectiveBalance * ($vault->annual_yield_rate / 100)) / 12;
            }
            return 0;
        });

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
