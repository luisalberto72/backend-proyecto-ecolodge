<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\HouseController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});




Route::get('/dashboard/{filename}', function ($filename) {
    $path = public_path('dashboard/' . $filename); // Ruta completa

    if (!file_exists($path)) {
        abort(404);
    }

    return Response::file($path);
});




