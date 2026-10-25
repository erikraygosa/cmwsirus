<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
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
            $response = $next($request);
            // El pipeline de Laravel puede convertir la excepción en respuesta antes de devolverla.
            $exception = $response->exception ?? null;
            if ($exception instanceof QueryException && (int) ($exception->errorInfo[1] ?? 0) === 1205) {
                return $this->blocked($request);
            }

            return $response;
        } catch (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1205) {
                throw $e;
            }

            return $this->blocked($request);
        } finally {
            config(['adminlte' => $original]);
            $elapsed = (int) ((microtime(true) - $started) * 1000);
            if ($elapsed >= 2000) {
                Log::warning('Combustible: solicitud lenta', ['ruta' => $request->route()?->getName(), 'duracion_ms' => $elapsed]);
            }
        }
    }

    private function blocked(Request $request)
    {
        Log::warning('Combustible: espera de bloqueo agotada', ['ruta' => $request->route()?->getName()]);
        $message = 'La base está ocupada por un bloqueo. Espere a que se libere antes de reintentar.';
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 503)->header('Retry-After', '10');
        }

        return response()->view('combustible.unavailable', ['message' => $message], 503)->header('Retry-After', '10');
    }
}
