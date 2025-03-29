<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $fillable = [
        'ecolodge_id', 'viajero_id', 'fecha_inicio', 'fecha_fin', 'estado', 'precio_total'
    ];

    // Relación con Ecolodge
    public function ecolodge()
    {
        return $this->belongsTo(Ecolodge::class, 'ecolodge_id');
    }

    // Relación con Viajero (User)
    public function viajero()
    {
        return $this->belongsTo(User::class, 'viajero_id');
    }

    public static function moverReservasFinalizadas()
{
    // Obtener reservas con estado "finalizada"
    $reservasFinalizadas = self::where('estado', 'finalizada')->get();

    foreach ($reservasFinalizadas as $reserva) {
        // Insertar en historial_reservas
        HistorialReserva::create([
            'reserva_id' => $reserva->id,
            'nombre_ecolodge' => $reserva->ecolodge->nombre,
            'ubicacion' => $reserva->ecolodge->ubicacion,
            'fecha_inicio' => $reserva->fecha_inicio,
            'fecha_fin' => $reserva->fecha_fin,
            'nombre_viajero' => $reserva->viajero->nombre,
            'precio_total' => $reserva->precio_total,
            'estado' => 'finalizada',
        ]);

        // Eliminar la reserva de la tabla original
        $reserva->delete();
    }
}

}