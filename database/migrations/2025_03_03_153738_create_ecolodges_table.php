<?php

// database/migrations/xxxx_xx_xx_create_ecolodges_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEcolodgesTable extends Migration
{
    public function up()
    {
        Schema::create('ecolodges', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Hacemos que el nombre sea único
            $table->text('descripcion');
            $table->string('ubicacion');
            $table->decimal('precio', 10, 2);
            $table->boolean('energia_renovable')->default(false);
            $table->boolean('paneles_solares')->default(false);
            $table->unsignedBigInteger('propietario_id');
            $table->foreign('propietario_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ecolodges');
    }
}

