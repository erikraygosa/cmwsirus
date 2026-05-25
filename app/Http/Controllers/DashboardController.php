<?php

namespace App\Http\Controllers;
use Illuminate\Support\Carbon;
use App\Models\PlanServ;
use App\Models\Tblaccidente;
use Illuminate\Support\Facades\DB;


use Illuminate\Http\Request;

class DashboardController extends Controller
{
    
    public function index()
    {

        $fechadia = Carbon::now()->format('d-m-Y');
        $fecha = Carbon::now();
        $unidadesProgramadas = PlanServ::whereDate('Fecha', $fecha)->count();

        // Contar las rutas del día
        $rutas = PlanServ::whereDate('Fecha', $fecha)
                         ->distinct('Ruta')
                         ->count('Ruta');

        $fecha = Carbon::now();
        
        $afectados =  DB::connection('mysql2')->table('Tblaccidentes')
        ->whereDate('Fechaini', $fecha)
        ->where('Responsable', 'N')
        ->count();
        $responsable =  DB::connection('mysql2')->table('Tblaccidentes')
        ->whereDate('Fechaini', $fecha)
        ->where('Responsable', 'S')
        ->count();
        $totalaccidentes = $responsable + $afectados;
        

        return view('dashboard',compact('fechadia', 'unidadesProgramadas', 'rutas', 'afectados', 'responsable', 'totalaccidentes' ));
    }
}
