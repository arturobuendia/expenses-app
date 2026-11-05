<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['category', 'vault', 'card']);

        // 1. Filtro de Búsqueda (Descripción)
        if ($request->filled('search')) {
            $query->where('description', 'like', '%'.$request->search.'%');
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
        $filteredExpenses = (clone $query)->get();

        // Las transferencias (ej. pago de tarjeta) mueven saldo pero no son gasto:
        // si contaran, el mismo dinero se contaría dos veces.
        $realExpenses = $filteredExpenses->where('is_transfer', false);
        $transfers = $filteredExpenses->where('is_transfer', true);

        $totals = [
            'total_amount' => $realExpenses->sum('amount'),
            'transaction_count' => $realExpenses->count(),
            'average_expense' => $realExpenses->count() > 0 ? $realExpenses->avg('amount') : 0,
            'biggest_expense' => $realExpenses->max('amount') ?? 0,
            'transfer_amount' => $transfers->sum('amount'),
            'transfer_count' => $transfers->count(),
        ];

        // 4. Paginamos (manteniendo los parámetros en la URL)
        $expenses = $query->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::active()->ofType('expense')->get(['id', 'name']);
        $vaults = Vault::orderBy('name')->get(['id', 'name']);
        $cards = Card::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'vaults' => $vaults,
            'cards' => $cards,
            'totals' => $totals,
            'filters' => $request->only(['search', 'category_id', 'date_filter']), // Regresamos el estado
        ]);
    }

    public function store(Request $request)
    {
        $expense = Expense::create($this->validatedData($request));
        $expense->syncInstallments();

        return redirect()->back()->with('success', 'Gasto registrado correctamente.');
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validatedData($request));
        $expense->syncInstallments();

        return redirect()->back()->with('success', 'Gasto actualizado.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();

        return redirect()->back()->with('success', 'Gasto eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'vault_id' => 'nullable|exists:vaults,id',
            'card_id' => 'nullable|exists:cards,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'date' => 'required|date',
            'is_transfer' => 'boolean',
            'installment_count' => 'nullable|integer|min:1|max:48',
        ]);

        $validated['is_transfer'] = $request->boolean('is_transfer');
        $validated['installment_count'] = (int) ($validated['installment_count'] ?? 1);

        if ($validated['is_transfer'] && empty($validated['vault_id'])) {
            throw ValidationException::withMessages([
                'vault_id' => 'Una transferencia necesita una bóveda de origen.',
            ]);
        }

        // Las parcialidades solo existen sobre una tarjeta.
        if ($validated['installment_count'] > 1 && empty($validated['card_id'])) {
            throw ValidationException::withMessages([
                'card_id' => 'Los meses sin intereses necesitan una tarjeta.',
            ]);
        }

        if ($validated['installment_count'] > 1 && $validated['is_transfer']) {
            throw ValidationException::withMessages([
                'installment_count' => 'Una transferencia no se puede diferir a meses.',
            ]);
        }

        // Un cargo o sale de una bóveda (efectivo/débito) o se debe en la
        // tarjeta. Las dos cosas a la vez descuadrarían ambos saldos.
        if (! empty($validated['card_id']) && ! empty($validated['vault_id']) && ! $validated['is_transfer']) {
            throw ValidationException::withMessages([
                'card_id' => 'Un cargo no puede salir de una bóveda y de una tarjeta a la vez.',
            ]);
        }

        // El cargo se asigna al corte que le corresponde. Los pagos no
        // pertenecen a un estado de cuenta: lo liquidan. Una compra diferida
        // tampoco: se reparte en parcialidades que cruzan varios cortes.
        if (! empty($validated['card_id']) && ! $validated['is_transfer'] && $validated['installment_count'] === 1) {
            $card = Card::findOrFail($validated['card_id']);
            $validated['card_statement_id'] = $card->statementFor($validated['date'])->id;
        } else {
            $validated['card_statement_id'] = null;
        }

        return $validated;
    }
}
