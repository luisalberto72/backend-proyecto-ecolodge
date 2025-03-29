<?php

namespace App\Http\Controllers;

use App\Models\Ecolodge;
use App\Models\ImagenEcolodge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EcolodgeController extends Controller
{
    public function index(Request $request)
{
    // Verifica si el usuario está autenticado
    if (!auth()->check()) {
        return response()->json(['error' => 'No autorizado'], 403);
    }

    // Obtener el usuario autenticado
    $user = auth()->user();

    // Permitir solo a "traveler" o "both" buscar ecolodges
    if (!in_array($user->role, ['traveler', 'both'])) {
        return response()->json(['error' => 'Acceso denegado'], 403);
    }

    // Consulta base sin filtros
    $query = Ecolodge::query();

    // Aplicar filtros si existen en la solicitud
    if ($request->has('paneles_solares')) {
        $query->where('paneles_solares', filter_var($request->paneles_solares, FILTER_VALIDATE_BOOLEAN));
    }

    if ($request->has('energia_renovable')) {
        $query->where('energia_renovable', filter_var($request->energia_renovable, FILTER_VALIDATE_BOOLEAN));
    }

    // Ejecutar la consulta después de aplicar los filtros
    $ecolodges = $query->get();

    return response()->json($ecolodges);
}

    

    public function store(Request $request)
    {
        // Obtén el usuario autenticado
        $user = auth()->user();
        // Verifica si el usuario tiene el rol 'traveler'
        if ($user->role == 'traveler') {
            return response()->json(['error' => 'Acción no permitida: solo los propietarios o both pueden agregar un ecolodge.'], 403);
        }

        $validatedData = $request->validate([
            'nombre' => 'required|unique:ecolodges',
            'descripcion' => 'required',
            'ubicacion' => 'required',
            'precio' => 'required|numeric',
            'disponible' => 'boolean',
            'paneles_solares' => 'boolean',
            'energia_renovable' => 'boolean', // Asegúrate de validar este campo también si es necesario
        ]);
      
        $validatedData['propietario_id'] = $user->id; // Tomamos el ID del usuario autenticado
    
        $ecolodge = Ecolodge::create($validatedData);
    
        return response()->json($ecolodge, 201);
    }
    
    
    public function update(Request $request, $id)
    {
        // Verificar que el usuario tenga rol owner o both
        if (!in_array(auth()->user()->role, ['owner', 'both'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
    
        // Encontrar el ecolodge y verificar que pertenece al usuario
        $ecolodge = Ecolodge::where('id', $id)
            ->where('propietario_id', auth()->id())
            ->first();

        if (!$ecolodge) {
            return response()->json(['error' => 'Ecolodge no encontrado o no autorizado'], 404);
        }
    
        // Validar la información
        $request->validate([
            'nombre' => 'required|string|max:255',
            'ubicacion' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'nullable|numeric|min:0',
            'paneles_solares' => 'boolean',
            'energia_renovable' => 'boolean',
        ]);
    
        // Actualizar campos
        $ecolodge->update([
            'nombre' => $request->nombre,
            'ubicacion' => $request->ubicacion,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'paneles_solares' => $request->paneles_solares,
            'energia_renovable' => $request->energia_renovable,
        ]);
    
        return response()->json(['message' => 'Ecolodge actualizado', 'ecolodge' => $ecolodge]);
    }
    
    public function destroy($id)
    {
        // Verificar que el usuario tenga rol owner o both
        if (!in_array(auth()->user()->role, ['owner', 'both'])) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Buscar el ecolodge y verificar que pertenece al usuario
        $ecolodge = Ecolodge::where('id', $id)
            ->where('propietario_id', auth()->id())
            ->firstOrFail(); // Esto lanza una excepción si no se encuentra el ecolodge

        // Eliminar las imágenes relacionadas
        $ecolodge->imagenes()->delete(); // Eliminar las imágenes relacionadas

        // Eliminar el ecolodge
        $ecolodge->delete();

        return response()->json(['message' => 'Ecolodge eliminado correctamente']);
    }

    public function filtrarEcolodges(Request $request)
    {
        $query = Ecolodge::query();

        if ($request->has('paneles_solares')) {
            $solar = filter_var($request->paneles_solares, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($solar)) {
                $query->where('paneles_solares', $solar);
            }
        }

        if ($request->has('energia_renovable')) {
            $energia = filter_var($request->energia_renovable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($energia)) {
                $query->where('energia_renovable', $energia);
            }
        }

        if (auth()->check()) {
            $query->where('propietario_id', auth()->id());
        } else {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        $ecolodges = $query->get();

        if ($ecolodges->isEmpty()) {
            return response()->json(['message' => 'No se encontraron ecolodges con los filtros aplicados'], 404);
        }

        return response()->json($ecolodges);
    }

    public function show($id)
    {
        $ecolodge = Ecolodge::findOrFail($id);
        // Obtener imágenes relacionadas
        $ecolodge->imagenes;
        return response()->json($ecolodge);
    }


   
public function getEcolodgeById($id)
{
    $ecolodge = Ecolodge::with('imagenes')->find($id);

    if ($ecolodge) {
        return response()->json($ecolodge);
    } else {
        return response()->json(['error' => 'Ecolodge no encontrado'], 404);
    }
}


public function storeImage(Request $request, $ecolodgeId)
{
    // Validar la imagen recibida
    $request->validate([
        'image' => 'required|image|mimes:jpg,jpeg,png|max:2048', // Puedes ajustar las validaciones
    ]);

    // Obtener el ecolodge por su ID
    $ecolodge = Ecolodge::findOrFail($ecolodgeId);

    // Verificar si la imagen fue subida
    if ($request->hasFile('image')) {
        // Guardar la imagen en la carpeta 'public/Houses/image'
        $imagePath = $request->file('image')->store('public/Houses/image');
        
        // Guardar la ruta de la imagen en la base de datos (quitar el prefijo 'public/')
        $ecolodge->imagenes = basename($imagePath);  // Solo almacenamos el nombre del archivo

        // Guardar el Ecolodge con la nueva imagen
        $ecolodge->save();
        
        return response()->json(['message' => 'Imagen subida exitosamente', 'imagen' => $imagePath], 200);
    }

    return response()->json(['message' => 'No se encontró ninguna imagen para subir'], 400);
}

public function uploadImage($id, Request $request)
{
    // Validación de las imágenes
    $request->validate([
        'images' => 'required|array',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validación de tipos y tamaño de archivo
    ]);

    // Buscar el Ecolodge por ID
    $ecolodge = Ecolodge::find($id);
    if (!$ecolodge) {
        return response()->json(['error' => 'Ecolodge no encontrado'], 404);
    }

    // Arreglo para almacenar las rutas de las imágenes
    $imagePaths = [];

    // Subir las imágenes
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            // Almacenar la imagen en el almacenamiento público
            $path = $image->store('public/Houses/image');  // Almacena en 'storage/app/public/Houses/image'

            // Obtener la ruta relativa
            $imagePath = str_replace('public/', '', $path);  // Eliminar 'public/' de la ruta

            // Crear una nueva entrada en la tabla 'imagenes_ecolodges'
            $ecolodge->imagenes()->create([
                'ruta_imagen' => $imagePath,
            ]);

            // Añadir la ruta al arreglo de respuestas
            $imagePaths[] = $imagePath;
        }
    }

    // Retornar las rutas de las imágenes
    return response()->json(['images' => $imagePaths], 200);
}


public function filterAll(Request $request)
{
    $panelesSolares = $request->get('paneles_solares', 0);
    $energiaRenovable = $request->get('energia_renovable', 0);
    $role = $request->get('role', null);

    $query = Ecolodge::query();

    if ($panelesSolares !== null) {
        $query->where('paneles_solares', $panelesSolares);
    }

    if ($energiaRenovable !== null) {
        $query->where('energia_renovable', $energiaRenovable);
    }

    if ($role) {
        // Filtrar por el rol del propietario
        $query->whereHas('propietario', function ($query) use ($role) {
            $query->where('role', $role);
        });
    }

    $ecolodges = $query->get();

    return response()->json($ecolodges);
}

public function obtenerImagenes($ecolodgeId)
    {
        // Obtén todas las imágenes asociadas a un ecolodge
        $imagenes = ImagenEcolodge::where('ecolodge_id', $ecolodgeId)->get();

        // Si las imágenes están en el almacenamiento público
        foreach ($imagenes as $imagen) {
            // Asegúrate de que las rutas de las imágenes sean accesibles públicamente
            $imagen->ruta_imagen = asset('storage/' . $imagen->ruta_imagen);  // Si usas almacenamiento en Laravel
        }

        return response()->json($imagenes);
    }
}
