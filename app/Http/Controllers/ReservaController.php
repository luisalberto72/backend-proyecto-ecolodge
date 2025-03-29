<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reserva;
use App\Models\Ecolodge;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;


class ReservaController extends Controller
{
    /**
     * Obtener todas las reservas.
     */
    public function index()
    {
        $reservas = Reserva::with('ecolodge', 'viajero')->get();
        return response()->json($reservas);
    }

    /**
     * Obtener una reserva específica.
     */
    public function show($id)
    {
        // Buscar la reserva con sus relaciones ecolodge y viajero
        $reserva = Reserva::with(['ecolodge', 'viajero'])->find($id);
    
        if (!$reserva) {
            // Si no se encuentra la reserva, devolver un error 404
            return response()->json(['error' => 'Reserva no encontrada'], 404);
        }
    
        // Si todo está bien, devolver la reserva con las relaciones cargadas
        return response()->json($reserva);
    }
    
    /**
     * Crear una nueva reserva.
     */
    public function store(Request $request)
    {
        // Validar los datos de la solicitud
        $request->validate([
            'ecolodge_id' => 'required|exists:ecolodges,id',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);

        // Verificar que el usuario sea un viajero ('traveler' o 'both')
        $user = Auth::user();
        if (!in_array($user->role, ['traveler', 'both'])) {
            return response()->json(['error' => 'No tienes permisos para reservar'], 403);
        }

        // Verificar que el ecolodge no esté reservado en las fechas seleccionadas
        $existeReserva = Reserva::where('ecolodge_id', $request->ecolodge_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('fecha_inicio', [$request->fecha_inicio, $request->fecha_fin])
                      ->orWhereBetween('fecha_fin', [$request->fecha_inicio, $request->fecha_fin])
                      ->orWhere(function ($query) use ($request) {
                          $query->where('fecha_inicio', '<=', $request->fecha_inicio)
                                ->where('fecha_fin', '>=', $request->fecha_fin);
                      });
            })
            ->exists();

        if ($existeReserva) {
            return response()->json(['error' => 'El ecolodge ya está reservado en estas fechas'], 400);
        }

        // Obtener el precio por noche del ecolodge
        $ecolodge = Ecolodge::findOrFail($request->ecolodge_id);
        $precioTotal = $ecolodge->precio_por_noche * (strtotime($request->fecha_fin) - strtotime($request->fecha_inicio)) / 86400;

        // Crear la reserva
        $reserva = Reserva::create([
            'ecolodge_id' => $request->ecolodge_id,
            'traveler_id' => $user->id,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'estado' => 'activa',
            'precio_total' => $precioTotal
        ]);

        return response()->json(['message' => 'Reserva creada exitosamente', 'reserva' => $reserva], 201);
    }

    /**
     * Cancelar una reserva.
     */
    public function cancelar($id)
    {
        $reserva = Reserva::find($id);

        if (!$reserva) {
            return response()->json(['error' => 'Reserva no encontrada'], 404);
        }

        // Solo el viajero que hizo la reserva puede cancelarla
        if (Auth::id() !== $reserva->traveler_id) {
            return response()->json(['error' => 'No tienes permisos para cancelar esta reserva'], 403);
        }

        $reserva->estado = 'cancelada';
        $reserva->save();

        return response()->json(['message' => 'Reserva cancelada exitosamente']);
    }


    public function crearReserva(Request $request) {
        // Validar los datos de entrada
        $request->validate([
            'ecolodge_id' => 'required|exists:ecolodges,id',
            'viajero_id' => 'required|exists:users,id',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);
    
        // Verificar que el ecolodge esté disponible en las fechas seleccionadas
        $existeReserva = Reserva::where('ecolodge_id', $request->input('ecolodge_id'))
            ->where(function ($query) use ($request) {
                $query->whereBetween('fecha_inicio', [$request->input('fecha_inicio'), $request->input('fecha_fin')])
                      ->orWhereBetween('fecha_fin', [$request->input('fecha_inicio'), $request->input('fecha_fin')])
                      ->orWhere(function ($q) use ($request) {
                          $q->where('fecha_inicio', '<=', $request->input('fecha_inicio'))
                            ->where('fecha_fin', '>=', $request->input('fecha_fin'));
                      });
            })
            ->exists();
    
        if ($existeReserva) {
            return response()->json(['error' => 'El ecolodge ya está reservado en esas fechas.'], 409);
        }
    
        // Obtener el precio por noche del ecolodge
        $ecolodge = Ecolodge::findOrFail($request->input('ecolodge_id'));
        $precioPorNoche = $ecolodge->precio;
    
        // Calcular la cantidad de días
        $fechaInicio = new \DateTime($request->input('fecha_inicio'));
        $fechaFin = new \DateTime($request->input('fecha_fin'));
        $dias = $fechaInicio->diff($fechaFin)->days;
    
        // Calcular precio total
        $precioTotal = $dias * $precioPorNoche;
    
        // Guardar la reserva
        $reserva = new Reserva();
        $reserva->ecolodge_id = $request->input('ecolodge_id');
        $reserva->viajero_id = $request->input('viajero_id');
        $reserva->fecha_inicio = $request->input('fecha_inicio');
        $reserva->fecha_fin = $request->input('fecha_fin');
        $reserva->precio_total = $precioTotal;
        $reserva->estado = 'confirmada'; // Estado inicial
        $reserva->save();
    
        return response()->json(['success' => true, 'reserva' => $reserva], 201);
    }
    
    public function verificarDisponibilidad(Request $request)
    {
        // Validar los datos de entrada
        $request->validate([
            'ecolodge_id' => 'required|exists:ecolodges,id',
            'viajero_id' => 'required|exists:users,id',
            'fecha_inicio' => 'required|date|after_or_equal:today',
            'fecha_fin' => 'required|date|after:fecha_inicio',
        ]);
    
        // Obtener el precio por noche del ecolodge
        $ecolodge = Ecolodge::findOrFail($request->input('ecolodge_id'));
        $precioPorNoche = $ecolodge->precio;
    
        // Calcular la cantidad de días
        $fechaInicio = new \DateTime($request->input('fecha_inicio'));
        $fechaFin = new \DateTime($request->input('fecha_fin'));
        $dias = $fechaInicio->diff($fechaFin)->days;
    
        // Calcular precio total
        $precioTotal = $dias * $precioPorNoche;
    
        // Verificar que el ecolodge esté disponible en las fechas seleccionadas
        $existeReserva = Reserva::where('ecolodge_id', $request->input('ecolodge_id'))
            ->where(function ($query) use ($request) {
                $query->whereBetween('fecha_inicio', [$request->input('fecha_inicio'), $request->input('fecha_fin')])
                      ->orWhereBetween('fecha_fin', [$request->input('fecha_inicio'), $request->input('fecha_fin')]);
            })
            ->exists();
    
        if ($existeReserva) {
            return response()->json(['error' => 'El ecolodge ya está reservado en esas fechas.'], 409);
        }
    
       
        return response()->json(['success' => true, 'message' => 'Reserva confirmada.']);
    }
    
    public function obtenerReservasUsuario($userId)
    {
        $reservas = Reserva::where('viajero_id', $userId)
            ->whereNotNull('ecolodge_id') // Evita reservas sin ecolodge
            ->join('ecolodges', 'reservas.ecolodge_id', '=', 'ecolodges.id')
            ->select('reservas.*', 'ecolodges.nombre as nombre_ecolodge', 'reservas.ecolodge_id')
            ->get();
    
        Log::info('Reservas obtenidas:', $reservas->toArray()); // Verifica qué devuelve
    
        return response()->json($reservas);
    }
    
public function cancelarReserva($reservaId)
{
    $reserva = Reserva::findOrFail($reservaId);

    if (now()->toDateString() >= $reserva->fecha_inicio) {
        return response()->json(['error' => 'No puedes cancelar una reserva que ya ha comenzado.'], 403);
    }

    $reserva->delete();
    return response()->json(['success' => 'Reserva cancelada correctamente.']);
}

public function moverReservasFinalizadas()
{
    Reserva::moverReservasFinalizadas();
    return response()->json(['message' => 'Reservas finalizadas movidas al historial'], 200);
}

public function obtenerHistorialReservas(Request $request)
{
    // Obtén el usuario autenticado
    $user = Auth::user();

    // Verifica si el usuario está autenticado
    if ($user) {
        try {
            // Obtén las reservas finalizadas del usuario con las relaciones
            $reservas = Reserva::with(['ecolodge', 'viajero'])  // Carga las relaciones
                                ->where('viajero_id', $user->id)
                                ->where('estado', 'finalizada')
                                ->get();

            return response()->json($reservas);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un problema al obtener las reservas: ' . $e->getMessage()], 500);
        }
    }

    return response()->json(['error' => 'No autenticado'], 401);
}


}
