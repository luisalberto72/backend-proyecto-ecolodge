<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecolodge_id')->constrained('ecolodges')->onDelete('cascade');
            $table->foreignId('viajero_id')->constrained('users')->onDelete('cascade');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['activa','confirmada','cancelada','finalizada'])->default('activa');
            $table->decimal('precio_total', 8, 2)->default(0.0); // No es necesario usar 'change' si la columna no existía antes
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservas');
    }
};
