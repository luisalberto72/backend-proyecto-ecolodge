<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;



use App\Http\Controllers\EcolodgeController;

use App\Http\Controllers\ReservaController;


use App\Http\Controllers\OpinionController;

use App\Http\Controllers\HistorialReservaController;





/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['jwt.auth'])->get('/user', function (Request $request) {
    return response()->json($request->user());
});

// Rutas públicas (sin autenticación)
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/ResetPass', [UserController::class, 'forgotPassword']);

// Validar token (puede ser útil para depuración)
Route::post('/TokenTest', [UserController::class, 'ValidateToken']);

// Middleware de autenticación JWT
Route::middleware(['jwt.auth'])->group(function () {
    // Rutas protegidas de ecolodges
    Route::post('/ecolodges', [EcolodgeController::class, 'store']);
    Route::get('/ecolodges', [EcolodgeController::class, 'index']);

    // Refrescar token JWT
    Route::post('/refresh-token', [UserController::class, 'refreshToken']);
});
   
Route::get('/userinfo/{id}', [UserController::class, 'userinfo']);

Route::post('/UserUpdate/{id}', [UserController::class, 'updateUser']);
Route::post('/update-profile-picture/{id}', [UserController::class, 'updateProfilePicture']);

// Actualizar un Ecolodge
Route::middleware('auth:api')->put('/ecolodges/{id}', [EcolodgeController::class, 'update']);

// Eliminar un Ecolodge
Route::middleware('auth:api')->delete('ecolodges/{id}', [EcolodgeController::class, 'destroy']);



// Filtro avanzado
Route::middleware('auth:api')->get('/ecolodges-filtrar', [EcolodgeController::class, 'filtrarEcolodges']);

Route::get('ecolodges/{id}', [EcolodgeController::class, 'show']);

Route::middleware('auth:api')->post('/ecolodges', [EcolodgeController::class, 'store']); // Crear ecolodge
Route::middleware('auth:api')->put('/ecolodges/{id}', [EcolodgeController::class, 'update']); // Actualizar ecolodge


Route::post('/ecolodges/{id}/image', [EcolodgeController::class, 'uploadImage']);

Route::middleware('auth:api')->get('/ecolodges/filtrar-todos', [EcolodgeController::class, 'filterAll']);


Route::get('/ecolodge/{id}/imagenes', [EcolodgeController::class, 'obtenerImagenes']);

Route::post('/reservas/verificar-disponibilidad', [ReservaController::class, 'verificarDisponibilidad']);

Route::post('/reservas/crear', [ReservaController::class, 'crearReserva']);

Route::get('/reservas/usuario/{userId}', [ReservaController::class, 'obtenerReservasUsuario']);

Route::delete('/reservas/cancelar/{reservaId}', [ReservaController::class, 'cancelarReserva']);

Route::get('reservas/{id}', [ReservaController::class, 'show']);

Route::get('/opiniones', [OpinionController::class, 'obtenerOpiniones']);

Route::post('/opiniones', [OpinionController::class, 'guardarOpinion']);

Route::get('/opiniones/filtrar', [OpinionController::class, 'filtrarOpiniones']);

Route::middleware('auth:api')->get('/historial-reservas', [ReservaController::class, 'obtenerHistorialReservas']);

