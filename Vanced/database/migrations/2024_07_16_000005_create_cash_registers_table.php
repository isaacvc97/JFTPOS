<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')/* ->nullOnDelete() */;
            $table->decimal('initial_amount', 10,2)->nullable();
            $table->decimal('closing_amount', 10,2)->nullable();
            $table->decimal('system_amount', 10,2)->nullable();
            $table->decimal('sales_amount', 10,2)->nullable();
            $table->decimal('purchases_amount', 10,2)->nullable();
            $table->decimal('income_amount', 10,2)->nullable();
            $table->decimal('expenses_amount', 10,2)->nullable();
            $table->enum('status', ['open', 'closed']);
            $table->string('note')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
