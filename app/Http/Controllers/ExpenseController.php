<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('category');

        // 1. Filtro de Búsqueda (Descripción)
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // 2. Filtro por Categoría
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // 3. Filtro por Fechas (Por defecto "este mes")
        $dateFilter = $request->input('date_filter', 'this_month');
        $now = Carbon::now();

        if ($dateFilter === 'this_month') {
            $query->whereMonth('date', $now->month)->whereYear('date', $now->year);
        } elseif ($dateFilter === 'last_month') {
            // Usamos subMonthNoOverflow para evitar saltos raros en días 31
            $lastMonth = $now->copy()->subMonthNoOverflow();
            $query->whereMonth('date', $lastMonth->month)->whereYear('date', $lastMonth->year);
        } elseif ($dateFilter === 'this_year') {
            $query->whereYear('date', $now->year);
        }
        // Si es 'all_time', no aplicamos where de fecha

        // Clonamos la query para calcular totales exactos sin que la paginación los corte
        $totalsQuery = clone $query;
        $filteredExpenses = $totalsQuery->get();

        $totals = [
            'total_amount' => $filteredExpenses->sum('amount'),
            'transaction_count' => $filteredExpenses->count(),
            'average_expense' => $filteredExpenses->count() > 0 ? $filteredExpenses->avg('amount') : 0,
            'biggest_expense' => $filteredExpenses->max('amount') ?? 0,
        ];

        // 4. Paginamos (manteniendo los parámetros en la URL)
        $expenses = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::active()->ofType('expense')->get(['id', 'name']);

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'totals' => $totals,
            'filters' => $request->only(['search', 'category_id', 'date_filter']), // Regresamos el estado
        ]);
    }

    // ... (store, update y destroy se quedan exactamente igual)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);
        Expense::create($validated);
        return redirect()->back()->with('success', 'Gasto registrado correctamente.');
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
        ]);
        $expense->update($validated);
        return redirect()->back()->with('success', 'Gasto actualizado.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->back()->with('success', 'Gasto eliminado.');
    }
}
