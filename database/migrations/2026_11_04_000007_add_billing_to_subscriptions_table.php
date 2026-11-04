<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una suscripción ahora sabe con qué tarjeta se cobra, a qué categoría
     * pertenece el gasto que genera y cada cuánto se repite.
     *
     * expenses.subscription_id marca los cargos que nacieron de una
     * suscripción; el índice único evita duplicarlos si el cobro se dispara
     * dos veces.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('card_id')
                ->nullable()
                ->after('amount')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->after('card_id')
                ->constrained()
                ->nullOnDelete();

            $table->string('frequency', 10)->default('monthly')->after('next_billing_date');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('subscription_id')
                ->nullable()
                ->after('card_statement_id')
                ->constrained()
                ->nullOnDelete();

            $table->unique(['subscription_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropUnique(['subscription_id', 'date']);
            $table->dropConstrainedForeignId('subscription_id');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('card_id');
            $table->dropColumn('frequency');
        });
    }
};
