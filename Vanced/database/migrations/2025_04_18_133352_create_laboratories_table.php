<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('laboratories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('ruc')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('representative_name')->nullable();
            $table->string('representative_phone')->nullable();
            $table->timestamps();
        });

        // Schema::table('medicines', function (Blueprint $table) {
        //     $table->foreignId('laboratory_id')->nullable()->constrained()->nullOnDelete();
        // });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratories');
    }
};
