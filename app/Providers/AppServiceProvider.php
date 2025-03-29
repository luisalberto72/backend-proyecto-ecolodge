<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Reserva;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
{
    Reserva::where('fecha_fin', '<', Carbon::now())
        ->where('estado', '!=', 'finalizada')
        ->update(['estado' => 'finalizada']);
}

}
