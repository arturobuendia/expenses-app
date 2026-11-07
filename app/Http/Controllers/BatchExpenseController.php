<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class BatchExpenseController extends Controller
{
    public function index()
    {
        return Inertia::render('BatchExpenses/Index', [
            'categories' => $this->categories(),
            'cards' => $this->cards(),
        ]);
    }

    public function preview(Request $request)
    {
        $result = $this->parseAndResolve($request);

        return Inertia::render('BatchExpenses/Index', [
            'categories' => $this->categories(),
            'cards' => $this->cards(),
            'preview' => $result['preview'],
        ]);
    }

    public function store(Request $request)
    {
        $result = $this->parseAndResolve($request);

        if ($result['errors'] !== []) {
            throw ValidationException::withMessages([
                'json' => $this->flattenErrors($result['errors']),
            ]);
        }

        $count = count($result['ok']);

        DB::transaction(function () use ($result) {
            foreach ($result['ok'] as $data) {
                app(ExpenseService::class)->create($data);
            }
        });

        return redirect()->route('batch-expenses.index')->with('success', "{$count} cargos registrados correctamente.");
    }

    /**
     * Valida el request, decodifica el JSON y resuelve las filas.
     *
     * @return array{card: Card, ok: array<int, array<string, mixed>>, errors: array<int, array<string, string>>, preview: array<string, mixed>}
     */
    private function parseAndResolve(Request $request): array
    {
        $validated = $request->validate([
            'card_id' => 'required|exists:cards,id',
            'json' => 'required|string',
        ]);

        $card = Card::findOrFail($validated['card_id']);

        $decoded = json_decode($validated['json'], true);

        if (! is_array($decoded)) {
            throw ValidationException::withMessages([
                'json' => ['El JSON no es válido.'],
            ]);
        }

        if (! array_is_list($decoded)) {
            throw ValidationException::withMessages([
                'json' => ['El JSON debe ser una lista (array) de objetos.'],
            ]);
        }

        if (count($decoded) === 0) {
            throw ValidationException::withMessages([
                'json' => ['El JSON está vacío. Agrega al menos un cargo.'],
            ]);
        }

        $categories = Category::active()->ofType('expense')->get();

        $resolved = $this->resolveRows($card, $decoded, $categories);

        return [
            'card' => $card,
            'ok' => $resolved['ok'],
            'errors' => $resolved['errors'],
            'preview' => $this->buildPreview($card, $decoded, $resolved['ok'], $resolved['errors']),
        ];
    }

    /**
     * Resuelve cada fila contra las categorías activas y las reglas de negocio.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  \Illuminate\Support\Collection<int, Category>  $categories
     * @return array{ok: array<int, array<string, mixed>>, errors: array<int, array<string, string>>}
     */
    private function resolveRows(Card $card, array $rows, $categories): array
    {
        $ok = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                $errors[$index] = ['_row' => 'Cada elemento debe ser un objeto.'];

                continue;
            }

            $rowErrors = [];

            $description = trim((string) ($row['description'] ?? ''));
            $date = trim((string) ($row['date'] ?? ''));
            $amount = $row['amount'] ?? null;
            $categoryName = trim((string) ($row['category'] ?? ''));

            if ($description === '') {
                $rowErrors['description'] = 'La descripción es obligatoria.';
            } elseif (mb_strlen($description) > 255) {
                $rowErrors['description'] = 'La descripción no puede superar 255 caracteres.';
            }

            if ($date === '' || strtotime($date) === false) {
                $rowErrors['date'] = 'La fecha es obligatoria y debe ser válida (Y-m-d).';
            }

            if (! is_numeric($amount) || (float) $amount < 0.01) {
                $rowErrors['amount'] = 'El monto debe ser un número mayor o igual a 0.01.';
            }

            $category = null;
            if ($categoryName === '') {
                $rowErrors['category'] = 'La categoría es obligatoria.';
            } else {
                $category = $categories->first(
                    fn (Category $c) => mb_strtolower($c->name) === mb_strtolower($categoryName)
                );

                if (! $category) {
                    $rowErrors['category'] = "La categoría \"{$categoryName}\" no existe o no es de gasto.";
                }
            }

            $installmentCount = 1;
            if (array_key_exists('installment_count', $row) && $row['installment_count'] !== null) {
                $raw = $row['installment_count'];

                if (! is_int($raw) && ! ctype_digit((string) $raw)) {
                    $rowErrors['installment_count'] = 'Los meses sin intereses deben ser un entero entre 1 y 48.';
                } else {
                    $installmentCount = (int) $raw;

                    if ($installmentCount < 1 || $installmentCount > 48) {
                        $rowErrors['installment_count'] = 'Los meses sin intereses deben estar entre 1 y 48.';
                    }
                }
            }

            if ($rowErrors !== []) {
                $errors[$index] = $rowErrors;

                continue;
            }

            $data = [
                'category_id' => $category->id,
                'vault_id' => null,
                'card_id' => $card->id,
                'amount' => (float) $amount,
                'description' => $description,
                'date' => $date,
                'is_transfer' => false,
                'installment_count' => $installmentCount,
            ];

            $ok[$index] = $data;
        }

        return ['ok' => $ok, 'errors' => $errors];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $ok
     * @param  array<int, array<string, string>>  $errors
     * @return array<string, mixed>
     */
    private function buildPreview(Card $card, array $rows, array $ok, array $errors): array
    {
        $items = [];
        $totalAmount = 0.0;
        $totalCount = 0;

        foreach ($rows as $index => $row) {
            $hasErrors = isset($errors[$index]);
            $data = $ok[$index] ?? null;

            $item = [
                'index' => $index,
                'date' => $row['date'] ?? null,
                'description' => $row['description'] ?? null,
                'amount' => $row['amount'] ?? null,
                'category' => $row['category'] ?? null,
                'installment_count' => $data['installment_count'] ?? ($row['installment_count'] ?? 1),
                'errors' => $hasErrors ? $errors[$index] : [],
            ];

            if ($data !== null) {
                $item['category_id'] = $data['category_id'];
                $item['installments'] = $this->previewSchedule($data);
                $totalAmount += (float) $data['amount'];
                $totalCount++;
            }

            $items[] = $item;
        }

        return [
            'card' => ['id' => $card->id, 'name' => $card->name],
            'items' => $items,
            'totals' => [
                'count' => $totalCount,
                'amount' => round($totalAmount, 2),
            ],
            'has_errors' => $errors !== [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{number: int, amount: float, cut_date: string}>
     */
    private function previewSchedule(array $data): array
    {
        return (new Expense($data))->installmentSchedule();
    }

    /**
     * @param  array<int, array<string, string>>  $errors
     * @return array<int, string>
     */
    private function flattenErrors(array $errors): array
    {
        $messages = [];

        foreach ($errors as $index => $rowErrors) {
            foreach ($rowErrors as $field => $message) {
                $label = $field === '_row' ? '' : " ({$field})";
                $messages[] = 'Fila '.($index + 1)."{$label}: {$message}";
            }
        }

        return $messages;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Category>
     */
    private function categories()
    {
        return Category::active()->ofType('expense')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Card>
     */
    private function cards()
    {
        return Card::where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
