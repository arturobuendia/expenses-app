<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un estado de cuenta por ciclo de facturación. Se materializa bajo demanda
     * (no hay cron en este proyecto) cuando una compra cae en el ciclo.
     */
    public function up(): void
    {
        Schema::create('card_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('cut_date');
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['card_id', 'cut_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_statements');
    }
};
