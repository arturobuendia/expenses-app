<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Vault;
use App\Models\Subscription;
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

        // 2. Tu Capital Total e Inversiones
        $vaults = Vault::orderByDesc('balance')->get();
        $totalCapital = $vaults->sum('balance');

        // 3. Próximos gastos fijos (Suscripciones)
        $upcomingSubscriptions = Subscription::where('is_active', true)
            ->where('next_billing_date', '>=', $now->toDateString())
            ->orderBy('next_billing_date', 'asc')
            ->take(5)
            ->get();

        // 4. Vistazo rápido a los últimos 5 gastos
        $latestExpenses = Expense::with('category')
            ->orderByDesc('date')
            ->orderByDesc('id') // Por si hay varios el mismo día
            ->take(5)
            ->get();

        // Retornamos la vista de Vue 3 con toda la data empaquetada
        return Inertia::render('Dashboard/Index', [
            'currentMonthName' => ucfirst($now->translatedFormat('F')), // Ej. "Marzo"
            'totals' => [
                'expenses' => (float) $totalExpenses,
                'incomes' => (float) $totalIncomes,
                'capital' => (float) $totalCapital,
            ],
            'vaults' => $vaults,
            'upcomingSubscriptions' => $upcomingSubscriptions,
            'latestExpenses' => $latestExpenses,
        ]);
    }
}
