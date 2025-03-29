<?php
namespace App\Http\Middleware;

use Closure;

class Cors
{
    public function handle($request, Closure $next)
    {
        // Manejar la solicitud OPTIONS (preflight)
        if ($request->getMethod() == "OPTIONS") {
            return response()->make('', 200, [
                'Access-Control-Allow-Origin' => 'http://localhost:4200',  // Origen de tu frontend
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',  // Métodos permitidos
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization',  // Cabeceras permitidas
                'Access-Control-Max-Age' => 3600,  // Cache por 1 hora
            ]);
        }

        // Continuar con el procesamiento normal de la solicitud
        $response = $next($request);

        // Agregar cabeceras CORS a la respuesta
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:4200');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        return $response;
    }
}
