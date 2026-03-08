<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = Income::with('category');

        // 1. Filtro de Búsqueda (Descripción)
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // 2. Filtro por Categoría
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // 3. Filtro por Fechas
        $dateFilter = $request->input('date_filter', 'this_month');
        $now = Carbon::now();

        if ($dateFilter === 'this_month') {
            $query->whereMonth('date', $now->month)->whereYear('date', $now->year);
        } elseif ($dateFilter === 'last_month') {
            $lastMonth = $now->copy()->subMonthNoOverflow();
            $query->whereMonth('date', $lastMonth->month)->whereYear('date', $lastMonth->year);
        } elseif ($dateFilter === 'this_year') {
            $query->whereYear('date', $now->year);
        }

        // Clonamos la query para calcular totales sin que la paginación los corte
        $totalsQuery = clone $query;
        $filteredIncomes = $totalsQuery->get();

        $totals = [
            'total_amount' => $filteredIncomes->sum('amount'),
            'transaction_count' => $filteredIncomes->count(),
            'average_income' => $filteredIncomes->count() > 0 ? $filteredIncomes->avg('amount') : 0,
            'biggest_income' => $filteredIncomes->max('amount') ?? 0,
        ];

        // 4. Paginamos (manteniendo parámetros en la URL)
        $incomes = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::active()->ofType('income')->get(['id', 'name']);

        return Inertia::render('Incomes/Index', [
            'incomes' => $incomes,
            'categories' => $categories,
            'totals' => $totals,
            'filters' => $request->only(['search', 'category_id', 'date_filter']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        Income::create($validated);

        return redirect()->back()->with('success', 'Ingreso registrado correctamente.');
    }

    public function update(Request $request, Income $income)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        $income->update($validated);

        return redirect()->back()->with('success', 'Ingreso actualizado.');
    }

    public function destroy(Income $income)
    {
        $income->delete();

        return redirect()->back()->with('success', 'Ingreso eliminado.');
    }
}