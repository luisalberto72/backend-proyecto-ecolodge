<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Opinion;
use App\Models\Ecolodge;
use App\Models\User;

class OpinionController extends Controller
{
  

    // Método para guardar una opinión
    public function guardarOpinion(Request $request)
    {
        // Validación de los datos recibidos
        $validator = \Validator::make($request->all(), [
            'ecolodge_id' => 'required|exists:ecolodges,id',
            'viajero_id' => 'required|exists:users,id',
            'calificacion' => 'required|integer|min:1|max:5',
            'comentario' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Error en la validación de datos',
                'detalles' => $validator->errors()
            ], 422);
        }
    
        try {
            // Verifica si el ecolodge y el viajero existen
            $ecolodge = Ecolodge::find($request->ecolodge_id);
            $viajero = User::find($request->viajero_id);
    
            if (!$ecolodge || !$viajero) {
                return response()->json(['error' => 'Ecolodge o Viajero no encontrado'], 404);
            }
    
            // Crear la opinión con los nombres
            $opinion = Opinion::create([
                'ecolodge_id' => $request->ecolodge_id,
                'viajero_id' => $request->viajero_id,
                'calificacion' => $request->calificacion,
                'comentario' => $request->comentario,
                'ecolodge_nombre' => $ecolodge->nombre,  // Guardar el nombre del ecolodge
                'viajero_nombre' => $viajero->first_name . ' ' . $viajero->last_name,  // Guardar el nombre completo del viajero
            ]);
    
            return response()->json([
                'success' => 'Opinión guardada correctamente.',
                'opinion' => $opinion
            ], 201);
        } catch (\Exception $e) {
            // Si ocurre un error al guardar la opinión, se maneja la excepción
            return response()->json([
                'error' => 'Error al guardar la opinión',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
    
    
    // Método para obtener todas las opiniones (usado para cargar opiniones en el frontend)
    public function obtenerOpiniones()
{
    try {
        $opiniones = Opinion::with(['ecolodge', 'viajero'])
            ->get()
            ->map(function ($opinion) {
                // Concatenar el nombre completo del viajero
                $opinion->viajero_nombre = $opinion->viajero->first_name . ' ' . $opinion->viajero->last_name;
                
                // Obtener solo el nombre del ecolodge (si es necesario)
                $opinion->ecolodge_nombre = $opinion->ecolodge->nombre;

                return $opinion;
            });
        
        return response()->json($opiniones);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al obtener las opiniones', 'detalle' => $e->getMessage()], 500);
    }
}

public function filtrarOpiniones(Request $request)
{
    $query = Opinion::with(['ecolodge', 'viajero'])
        ->select('opiniones.*', 'created_at as fecha'); // Alias para devolverlo como 'fecha'

    // Filtrar por ecolodge
    if ($request->has('ecolodge_nombre') && $request->ecolodge_nombre != '') {
        $query->whereHas('ecolodge', function ($q) use ($request) {
            $q->where('nombre', 'like', '%' . $request->ecolodge_nombre . '%');
        });
    }

    // Filtrar por calificación
    if ($request->has('calificacion') && $request->calificacion != '') {
        $query->where('calificacion', $request->calificacion);
    }

    // Ordenar por fecha
    if ($request->has('orden') && in_array($request->orden, ['asc', 'desc'])) {
        $query->orderBy('created_at', $request->orden);
    } else {
        $query->orderBy('created_at', 'desc');
    }

    $opiniones = $query->get();

    return response()->json($opiniones);
}


public function obtenerEcolodges()
{
    return response()->json(Ecolodge::select('id', 'nombre')->get());
}

}
