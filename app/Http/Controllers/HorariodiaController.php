<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PlanServ;
use App\Models\catcorrida;
use App\Models\Ruta;
use App\Models\Mensaje;
use Illuminate\Support\Facades\DB;


class HorariodiaController extends Controller
{
    public function index()
    {

        $fechaProgramada = Carbon::now()->format('d-m-Y');
        $fecha = Carbon::now();

        $message = Mensaje::where('status', '1')->first();

        $pservicios = PlanServ::whereDate('Fecha', $fecha)
                    ->orderBy('IdRuta')
                      ->get();
        
                      $corridas = catcorrida::whereIn('Corrida', $pservicios->pluck('Corrida'))->get()->keyBy('Corrida');
                      $rutas = Ruta::whereIn('IdRuta', $pservicios->pluck('IdRuta'))->get()->keyBy('IdRuta');
        
      
        return view('horariodia.index',compact('fechaProgramada', 'pservicios','corridas', 'rutas','message'));
    }

    public function av()
{

    $tituloHorario = env('HORARIO_TITULO', 'GRUPO');
    $rowsPerPage = env('HORARIO_FILAS', 10);
    $intervaloMs = env('HORARIO_INTERVALO_MS', 30000);


    $fechaCarbon = Carbon::now(); // Mismo que en index()
    $fechaProgramada = $fechaCarbon->format('d-m-Y'); // Esto sí puede ser string para mostrar

    // Obtener servicios del día
    $pserviciosRaw = PlanServ::whereDate('Fecha', $fechaCarbon)
        ->orderBy('IdRuta')
        ->get();

    // Obtener catálogos relacionados
    $corridas = catcorrida::whereIn('Corrida', $pserviciosRaw->pluck('Corrida'))
        ->get()
        ->keyBy('Corrida');

    $rutas = Ruta::whereIn('IdRuta', $pserviciosRaw->pluck('IdRuta'))
        ->get()
        ->keyBy('IdRuta');

    // Preparar mensaje
    $message = Mensaje::where('status', 1)->first();
    if (!$message) {
        $message = (object) ['mensaje' => '🚌 ¡Bienvenidos! Consulte las rutas y horarios programados del día.'];
    }

    // Transformar datos para JS
    $pservicios = $pserviciosRaw->map(function ($p) use ($rutas, $corridas) {
        $ruta = $rutas[$p->IdRuta] ?? null;
        $corrida = $corridas[$p->Corrida] ?? null;

        return [
            'ruta'     => $ruta->Ruta ?? 'Sin Asignar',
            'operador' => $p->Operador,
            'unidad'   => $p->Unidad,
            'corrida'  => $p->Corrida,
            'turno'    => $p->Turno,
            'horaIni'  => $corrida ? ($p->Turno == 'AM' ? $corrida->HoraIniAM : $corrida->HoraIniPM) : 'Sin Asignar',
            'horaFin'  => $corrida ? ($p->Turno == 'AM' ? $corrida->HoraFinAM : $corrida->HoraFinPM) : 'Sin Asignar',
        ];
    });

    return view('horariodia_aeropuerto', compact('fechaProgramada', 'pservicios', 'message','tituloHorario', 'rowsPerPage', 'intervaloMs'));
}

    public function biometrico()
{
    return view('biometrico');
}

public function tv()
{
    $fechaCarbon = Carbon::now();
    $fechaProgramada = $fechaCarbon->format('d-m-Y');

    $pserviciosRaw = PlanServ::whereDate('Fecha', $fechaCarbon)
        ->orderBy('IdRuta')
        ->get();

    $corridas = catcorrida::whereIn('Corrida', $pserviciosRaw->pluck('Corrida'))->get()->keyBy('Corrida');
    $rutas = Ruta::whereIn('IdRuta', $pserviciosRaw->pluck('IdRuta'))->get()->keyBy('IdRuta');

    $message = Mensaje::where('status', 1)->first();
    if (!$message) {
        $message = (object) ['mensaje' => '🚌 ¡Bienvenidos! Consulte las rutas y horarios programados del día.'];
    }

    $tituloHorario = env('HORARIO_TITULO', 'Grupo Comi');
    $rowsPerPage = env('HORARIO_FILAS', 10);
    $intervaloMs = env('HORARIO_INTERVALO_MS', 30000);

    $pservicios = $pserviciosRaw->map(function ($p) use ($rutas, $corridas) {
        $ruta = $rutas[$p->IdRuta] ?? null;
        $corrida = $corridas[$p->Corrida] ?? null;

        return [
            'ruta'     => $ruta->Ruta ?? 'Sin Asignar',
            'operador' => $p->Operador,
            'unidad'   => $p->Unidad,
            'corrida'  => $p->Corrida,
            'turno'    => $p->Turno,
            'horaIni'  => $corrida ? ($p->Turno == 'AM' ? $corrida->HoraIniAM : $corrida->HoraIniPM) : 'Sin Asignar',
            'horaFin'  => $corrida ? ($p->Turno == 'AM' ? $corrida->HoraFinAM : $corrida->HoraFinPM) : 'Sin Asignar',
        ];
    });

    return view('horariodia.tv', compact('fechaProgramada', 'pservicios', 'message', 'tituloHorario', 'rowsPerPage', 'intervaloMs'));
}

}
