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
        Schema::create('cart_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_sale_id')->constrained('cart_sales')->onDelete('cascade');
            $table->foreignId('medicine_presentation_id')->constrained('medicine_presentations')->onDelete('cascade');
            $table->integer('cantidad');
            $table->decimal('precio', 10, 2);
            $table->decimal('descuento', 5, 2)->default(0);
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_sale_items');
    }
};
