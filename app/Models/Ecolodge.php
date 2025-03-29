<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ecolodge extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'descripcion', 'ubicacion', 'precio', 'paneles_solares', 'energia_renovable', 'propietario_id'];

    // Relación con ImagenEcolodge (uno a muchos)
    public function imagenes()
    {
        return $this->hasMany(ImagenEcolodge::class);
    }

    // Relación con el propietario (uno a muchos)
    public function propietario()
    {
        return $this->belongsTo(User::class, 'propietario_id');
    }

     // Relación inversa con Reserva
     public function reservas()
     {
         return $this->hasMany(Reserva::class);
     }

     // Relación con Opiniones
    public function opiniones()
    {
        return $this->hasMany(Opinion::class);
    }

}
