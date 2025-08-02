<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->dateTime('date');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['Efectivo','Tarjeta','Transferencia']);
            // $table->string('method')->nullable(); // efectivo, transferencia, etc
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_payments');
    }
};
