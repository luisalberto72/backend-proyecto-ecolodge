<?php

// Migración para imagenes_ecolodges (database/migrations/xxxx_xx_xx_create_imagenes_ecolodges_table.php)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagenesEcolodgesTable extends Migration
{
    /**
     * Ejecuta las migraciones.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imagenes_ecolodges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ecolodge_id')->constrained('ecolodges')->onDelete('cascade'); // Relación con ecolodges
            $table->string('ruta_imagen'); // Ruta de la imagen
            $table->timestamps();
        });
    }

    /**
     * Revierte las migraciones.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imagenes_ecolodges');
    }
}
