<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CombustibleUi
{
    public function handle(Request $request, Closure $next)
    {
        $original = config('adminlte');
        $started = microtime(true);
        config(['adminlte.preloader.enabled' => false, 'adminlte.google_fonts.allowed' => false]);
        foreach (array_keys(config('adminlte.plugins', [])) as $plugin) {
            config(["adminlte.plugins.$plugin.active" => false]);
        }
        try {
            return $next($request);
        } finally {
            config(['adminlte' => $original]);
            $elapsed = (int) ((microtime(true) - $started) * 1000);
            if ($elapsed >= 2000) {
                Log::warning('Combustible: solicitud lenta', ['ruta' => $request->route()?->getName(), 'duracion_ms' => $elapsed]);
            }
        }
    }
}
