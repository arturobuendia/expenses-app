<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Expense;
use Illuminate\Validation\ValidationException;

/**
 * Reglas de negocio compartidas para gastos.
 *
 * Tanto el alta individual (ExpenseController) como la importación por lote
 * (BatchExpenseController) deben pasar por aquí: un único lugar donde viven
 * las reglas cruzadas y la asignación del estado de cuenta.
 */
class ExpenseService
{
    /**
     * Aplica las reglas de negocio cruzadas a un gasto ya validado por campo y
     * resuelve el estado de cuenta (card_statement_id) que le corresponde.
     *
     * @param  array<string, mixed>  $data  category_id, vault_id, card_id,
     *                                      amount, description, date,
     *                                      is_transfer, installment_count
     * @return array<string, mixed>
     */
    public function resolve(array $data): array
    {
        $data['is_transfer'] = filter_var($data['is_transfer'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['installment_count'] = (int) ($data['installment_count'] ?? 1);

        if ($data['is_transfer'] && empty($data['vault_id'])) {
            throw ValidationException::withMessages([
                'vault_id' => 'Una transferencia necesita una bóveda de origen.',
            ]);
        }

        // Las parcialidades solo existen sobre una tarjeta.
        if ($data['installment_count'] > 1 && empty($data['card_id'])) {
            throw ValidationException::withMessages([
                'card_id' => 'Los meses sin intereses necesitan una tarjeta.',
            ]);
        }

        if ($data['installment_count'] > 1 && $data['is_transfer']) {
            throw ValidationException::withMessages([
                'installment_count' => 'Una transferencia no se puede diferir a meses.',
            ]);
        }

        // Un cargo o sale de una bóveda (efectivo/débito) o se debe en la
        // tarjeta. Las dos cosas a la vez descuadrarían ambos saldos.
        if (! empty($data['card_id']) && ! empty($data['vault_id']) && ! $data['is_transfer']) {
            throw ValidationException::withMessages([
                'card_id' => 'Un cargo no puede salir de una bóveda y de una tarjeta a la vez.',
            ]);
        }

        // El cargo se asigna al corte que le corresponde. Los pagos no
        // pertenecen a un estado de cuenta: lo liquidan. Una compra diferida
        // tampoco: se reparte en parcialidades que cruzan varios cortes.
        if (! empty($data['card_id']) && ! $data['is_transfer'] && $data['installment_count'] === 1) {
            $card = Card::findOrFail($data['card_id']);
            $data['card_statement_id'] = $card->statementFor($data['date'])->id;
        } else {
            $data['card_statement_id'] = null;
        }

        return $data;
    }

    /**
     * Crea un gasto aplicando las reglas de negocio y su calendario MSI.
     */
    public function create(array $data): Expense
    {
        $expense = Expense::create($this->resolve($data));
        $expense->syncInstallments();

        return $expense;
    }
}
