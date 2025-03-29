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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name'); // Primer nombre
            $table->string('last_name');  // Apellidos
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['owner', 'traveler','both'])->default('traveler'); // Tipo de usuario: propietario o viajero
            $table->string('address')->nullable();
            $table->string('gender')->nullable();
            $table->string('nationality')->nullable();
            $table->string('profile_picture')->nullable()->default('userdefault.jpg'); // Imagen de perfil
            $table->string('phone_number')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

