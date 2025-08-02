<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')/* ->default('1') */->constrained('clients')/* ->nullOnDelete() */;
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('sale_type', ['Contado','Credito']);
            $table->enum('payment_type', ['Efectivo','Tarjeta','Transferencia']);
            $table->decimal('total', 10, 2);
            $table->decimal('pago', 10, 2);
            $table->decimal('cambio', 10, 2);
            $table->enum('status', ['Procesada','Anulada','Pendiente','Cancelada']);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
