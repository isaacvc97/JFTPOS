<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['por_pagar', 'por_cobrar']);
            $table->enum('status', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->date('due_date')->nullable();

            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete(); // para por_cobrar
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();     // para por_cobrar

            $table->foreignId('laboratory_id')->nullable()->constrained('laboratories')->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()/* ->constrained('purchases')->nullOnDelete() */; // para por_pagar

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};