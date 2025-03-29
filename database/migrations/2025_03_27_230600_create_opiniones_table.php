<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('opiniones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecolodge_id')->constrained('ecolodges')->onDelete('cascade');
            $table->foreignId('viajero_id')->constrained('users')->onDelete('cascade');
            $table->string('ecolodge_nombre', 255);
            $table->string('viajero_nombre', 255);
            $table->tinyInteger('calificacion')->unsigned()->comment('Valor entre 1 y 5');
            $table->text('comentario');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('opiniones');
    }
};
