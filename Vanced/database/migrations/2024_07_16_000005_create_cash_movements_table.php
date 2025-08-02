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
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('cash_register_id')->constrained()/* ->nullOnDelete() */;
            $table->tinyInteger('user_id')->constrained()/* ->nullOnDelete() */;
            $table->enum('type', ['income', 'expense']);
            $table->string('reason')->nullable();
            $table->decimal('amount', 10,2)->nullable();
            $table->tinyInteger('related_id')->nullable();
            $table->tinyInteger('related_type')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
