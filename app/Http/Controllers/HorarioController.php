<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PlanServ;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class HorarioController extends Controller
{
    public function index()
    {

        $fechaProgramada = Carbon::now()->format('d-m-Y');
        $fecha = Carbon::now();
        $fecha2 = Carbon::now()->addDay();

        $empleados = DB::connection('mysql3')
                         ->table('catoperadores')
                         ->where('Estatus', 'A')
                         ->orderBy('Operador', 'asc')
                         ->get();

        $pservicios = PlanServ::where('fecha',$fechaProgramada)
        ->orderBy('Ruta')
        ->get();
        
       

        return view('horarios.index',compact('fechaProgramada', 'pservicios', 'empleados' ));
    }
    public function store(Request $request)
    {
        $fechaini = $request->fecha_inicio;
        $fechaProgramada = $fechaini;
        $fecha = Carbon::now();
        $fecha2 = Carbon::now()->addDay();

        $pservicios = PlanServ::where('fecha',$fechaini)
        ->orderBy('Ruta')
        ->get();
        
       
        

        return view('horarios.index',compact('fechaProgramada', 'pservicios', ));
    }
    public function updateRecord(Request $request)
{
    // Validar los datos recibidos
  dd($request);
    // Validar los datos recibidos
    $request->validate([
        'idreg' => 'required|integer',
        'fecha' => 'required|date',
        'turno' => 'required|string',
        'idunidad' => 'required|integer',
        'operador' => 'required|string',
    ]);

    // Buscar el registro utilizando las columnas de la clave primaria compuesta
    $record = PlanServ::where([
        'idreg' => $request->input('idreg'),
        'fecha' => $request->input('fecha'),
        'turno' => $request->input('turno'),
        'idunidad' => $request->input('idunidad'),
    ])->first();

    if ($record) {
        // Actualizar los datos del registro
        $record->update([
            'operador' => $request->input('operador'),
        ]);
        return redirect()->back()->with('success', 'Registro actualizado exitosamente');
    }

    return redirect()->back()->with('error', 'Registro no encontrado');
}
public function edit($id)
{
    
    $pservicios = PlanServ::where('ID', $id )
    

    ->first();

//    dd($pservicios->Fecha);
    // dd($pservicios);

    $fechaProgramada = Carbon::tomorrow()->format('d-m-Y');
    $fecha = Carbon::now();
    $fecha2 = Carbon::now()->addDay();
    $Operador = PlanServ::where('ID', $id)
    ->whereDate('Fecha',$pservicios->Fecha) 
    ->pluck('Operador');
    
    $empleados = DB::connection('mysql3')
                     ->table('catoperadores')
                     ->where('Estatus', 'A')
                     ->whereNotIn('Operador', $Operador)
                     ->orderBy('Operador', 'asc')
                     ->get();

  
                

    return view('horarios.edit',compact('fechaProgramada', 'pservicios', 'empleados' ));
}

public function update(Request $request, string $id)
{
  
    // $pservicio = PlanServ::where('ID', $id)->first();
    // $operador = DB::connection('mysql3')
    // ->table('catoperadores')
    // ->where('IdOper', $request->operador)
    // ->first();


   
    // $pservicio->update([
    //     'IdOper' => $request->input('operador'),
    //     'Operador' => $operador->Operador,
    // ]);
   

    return redirect()->back()->with('error', 'Modulo Demo Favor de Solicitar Activacion');


}
    

    
}
