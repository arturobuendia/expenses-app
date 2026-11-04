<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej. BBVA Azul, Nu Crédito
            $table->decimal('credit_limit', 12, 2);
            $table->unsignedTinyInteger('cut_off_day'); // Día de corte (1-31)
            $table->unsignedTinyInteger('payment_day'); // Día límite de pago (1-31)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
