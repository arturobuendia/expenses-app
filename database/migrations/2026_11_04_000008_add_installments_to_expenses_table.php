<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una compra a meses sin intereses sigue siendo una sola compra: debe el
     * total completo desde el día uno, y por eso su monto entra a la deuda de
     * la tarjeta. Pero lo que se factura cada mes es una parcialidad, así que
     * el calendario se materializa en filas propias.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedTinyInteger('installment_count')->default(1)->after('is_transfer');
        });

        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->decimal('amount', 12, 2);
            $table->date('cut_date');
            $table->timestamps();

            $table->unique(['expense_id', 'number']);
            $table->index(['card_id', 'cut_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installments');

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('installment_count');
        });
    }
};
