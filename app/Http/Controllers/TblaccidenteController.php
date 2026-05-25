<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use App\Models\Tblaccidente;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Unidad;
use App\Models\Ruta;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\catcausalesacc;
use App\Models\AccidenteImagen;
use Illuminate\Support\Facades\Auth;
use App\Models\CatzonaRuta;

use Dompdf\Dompdf;

class TblaccidenteController extends Controller
{
   

    public function index()
    {
        $accidentes = Tblaccidente::orderBy('Fechaini', 'desc')->get(); 
        return view('accidentes.index')->with('accidentes',$accidentes);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $unidades = Unidad::all();
        $rutas = Ruta::all();
        $empleados = Empleado::where('Estatus', 'A')
                     ->where('IdPuesto', 1)
                     ->orderBy('Nombre', 'asc')
                     ->get();

        $causales = catcausalesacc::all();
        $user = Auth::user();
        $catzonas  = CatzonaRuta::all();

        
        
        return view('accidentes.create', compact('unidades', 'rutas', 'empleados', 'causales','user','catzonas'));
        
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


        //1 = OPERADOR
        //2 = TERCERO EXTERNO
        //3 = COSTOS DE OPERACION
        //4 = TERCERO INTERNO

        //OBTENER EL TIPO DE ACCIDENTE
        $hh_accidente = $request->input('hh_accidente'); 
        

     

        //OBTENER ULTIMO FOLIO
        $tipo = $hh_accidente; // el tipo que quieres buscar
        $ultimoFolio =  DB::connection('mysql2')
                         ->table('tblaccidentes')
                        ->where('Tipo', $tipo)
                        ->orderBy('Folio', 'DESC')
                        ->value('Folio');


        $folio = $ultimoFolio + 1;
        



        //OBTENER ULTIMO REGISTRO IDHOJASERV
       
       // $ultimoIdResponse = DB::connection('mysql2')->select('SELECT IdHojaServ FROM `tblaccidentes` ORDER BY `IdHojaServ` DESC LIMIT 1');
       // $ultimoId = $ultimoIdResponse[0]->IdHojaServ;

      //  $IdHojaServ = $ultimoId+1;







       //OBTENER DATOS DE FECHA Y HORA
       $fechaActual = Carbon::now()->toDateString();
       $horaActual = Carbon::now()->toTimeString();



       



        //DATOS GENERALES
        $dg_fecha = $request->input('dg_fecha'); 
        $dg_hora = $request->input('dg_hora'); 
        $dg_lugarAccidente = $request->input('dg_lugarAccidente');
        $dg_descripcionAccidente = $request->input('dg_descripcionAccidente');
        $dg_radioCargarCostos = $request->input('dg_radioCargarCostos');
        $dg_responsable = $request->input('dg_responsable');
        $dg_presupuestar = $request->input('dg_presupuestar');
        $ConcepCau = $request->input('ConcepCau');
        $formadepagotercero = $request->input('FormaPagoTer');
        $sindicato = $request->input('Sindicato');

        
        

        $ConcepCauROW = catcausalesacc::find($ConcepCau);
        $conceptocau = $ConcepCauROW->Concepto;



        //DATOS DE LA UNIDAD
        $du_unidad = $request->input('du_unidad');
        $du_turno = $request->input('du_turno');
        $du_ruta = $request->input('du_ruta');
        $du_operador = $request->input('du_operador');
        $du_licencia = $request->input('du_licencia');
        $du_marca = $request->input('du_marca');
        $du_modelo = $request->input('du_modelo');
        $du_año = $request->input('du_año');
        $du_color = $request->input('du_color');
        $du_placas = $request->input('du_placas');
        $du_danos = $request->input('du_danos');


        //DATOS TERCEROS
        $dt_check = $request->input('dt_check');
        $dt_propietario = $request->input('dt_propietario');
        $dt_telefonoPropietario = $request->input('dt_telefonoPropietario');
        $dt_conductor = $request->input('dt_conductor');
        $dt_telefonoConductor = $request->input('dt_telefonoConductor');
        $dt_marca = $request->input('dt_marca');
        $dt_modelo = $request->input('dt_modelo');
        $dt_año = $request->input('dt_año');
        $dt_color = $request->input('dt_color');
        $dt_placas = $request->input('dt_placas');
        $dt_daños = $request->input('dt_daños');

        //OTROS DATOS

        $ot_lesionadosCheck = $request->input('ot_lesionadosCheck');
        $ot_lesionadosExtra = $request->input('ot_lesionadosExtra');

        
        $ot_datosAseguradoraCheck = $request->input('ot_datosAseguradoraCheck');
        $ot_datosAseguradora = $request->input('ot_datosAseguradora');
        $ot_numeroCq = $request->input('ot_numeroCq');
        $ot_ajustador = $request->input('ot_ajustador');
        $ot_costoAproximado = $request->input('ot_costoAproximado');
        $ot_sspCheck = $request->input('ot_sspCheck');
        $ot_perito = $request->input('ot_perito');
   
        $ot_gerente = $request->input('ot_gerente');
        $ot_observaciones = $request->input('ot_observaciones');




        //BINDEO DE PARAMETROS 
        $accidente = new Tblaccidente;
        $accidente->Folio = $folio;
       // $accidente->IdHojaServ = $IdHojaServ;
        $accidente->FechaIni = $fechaActual;
        $accidente->HoraIni = $horaActual;
        $accidente->Tipo = $tipo;

        $accidente->Fecha = $dg_fecha;
        $accidente->Lugar = $dg_lugarAccidente;
        $accidente->Hora = $dg_hora;
        $accidente->Descripcion = $dg_descripcionAccidente;
        $accidente->CargoCostos = $dg_radioCargarCostos;

        //A REVISAR
        $accidente->Responsable = strpos($dg_responsable, 's') !== false ? 'S' : 'N';
        $accidente->Presupuestar = strpos($dg_presupuestar, 's') !== false ? 'S' : 'N';

        //2TAB
        $accidente->IdUnidad = $du_unidad;
        //OBTENER LA UNIDAD CON SU ID
        $unidadRow = Unidad::find($du_unidad);
        $unidad = $unidadRow->Unidad;
        //
        $accidente->Unidad = $unidad;
        $accidente->Turno = $du_turno;
        $accidente->IdRuta = $du_ruta;


        //OBTENER LA RUTA CON SU ID
        $rutaRow = Ruta::find($du_ruta);
        $ruta = $rutaRow->Ruta;
        //

        $accidente->Ruta = $ruta;
        $accidente->IdOper = $du_operador;

        //OBTENER EL NOMBRE DEL OPERADOR CON SU ID
        $empleadoRow = Empleado::find($du_operador);
        $empleado = $empleadoRow->Nombre;
        //
        
        $accidente->Operador = $empleado;
        $accidente->Licencia = $du_licencia;
        $accidente->Marca2 = $du_marca;
        $accidente->Modelo2 = $du_modelo;
        $accidente->Anio2 = $du_año;
        $accidente->Color2 = $du_color;
        $accidente->Placas2 = $du_placas;
        $accidente->DaniosUnidad = $du_danos;

        //TAB3

        if($dt_check === 'on'){
            $accidente->DatosTercero = 'S';

            $accidente->Propietario = $dt_propietario;
            $accidente->TelProp = $dt_telefonoPropietario;
            
            $accidente->Conductor = $dt_conductor;
            $accidente->TelConduc = $dt_telefonoConductor;

            $accidente->Marca1 = $dt_marca;
            $accidente->Modelo1 = $dt_modelo;
            $accidente->Anio1 = $dt_año;
            $accidente->Color1 = $dt_color;
            $accidente->Placas1 = $dt_placas;
            $accidente->DaniosTercero = $dt_daños;
        } else{
            $accidente->DatosTercero = 'N';
        }



        //TAB4

        if($ot_lesionadosCheck === 'on'){
            $accidente->Lesionados = 'S';
            $accidente->NomLesionados = $ot_lesionadosExtra;


        } else{
            $accidente->Lesionados = 'N';
        }


        if($ot_datosAseguradoraCheck === 'on'){
            $accidente->Aseguradora = 'S';
            $accidente->Ajustador = $ot_ajustador;
            $accidente->NumCQ = $ot_numeroCq;
            $accidente->CostoAprox = $ot_costoAproximado;
        } else{
            $accidente->Aseguradora = 'N';
        }


        if($ot_sspCheck === 'on'){
            $accidente->SSP = 'S';
            $accidente->Perito = $ot_perito;
        } else{
            $accidente->SSP = 'N';
        }

        $accidente->Gerente = $ot_gerente;
        $accidente->Observ = $ot_observaciones;

        $accidente->IdCau = $ConcepCau;
        $accidente->ConcepCau = $conceptocau;
        $accidente->FormaPagoTer = $formadepagotercero;    
        $accidente->Sindicato = $sindicato;

        $user = Auth::user();
        $user_web = $user->name;
        $accidente->user_web = $user_web ;
        $accidente->IdZona = $request->input('IdZona');

      
        




         

       $accidente->save();









        
       return redirect()->route('accidentes.index')
                     ->with('success', 'El accidente se ha agregado correctamente.');

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $accidente1 = Tblaccidente::find($id);
      
        $accidentes = AccidenteImagen::where('IdRegTab', $id)->get();
       
        return view('accidentes.show', compact('accidentes', 'id'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $accidente = Tblaccidente::find($id);
      

      
        return view('accidentes.edit', compact('id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        



    }


    public function cambiarEstado(Request $request)
    {
        

        $id_accidente = $request->input('id_accidente');
        $seleccion = $request->input('modal3-select');
        $estatus = null;

        switch($seleccion){
            case "abierto":
                $estatus = 1;
                break;

                case "procesando":
                    $estatus = 2;
                    break;

                    case "presupuestado":
                        $estatus = 3;
                        break;


                case "cerrado":
                    $estatus = 4;
                    break;
        }

        $accidente = TblAccidente::find($id_accidente);

        if (!$accidente) {
            return redirect()->route('accidentes.index')->with('error', 'No se ha podido encontrar el accidente.');
        }
        
        $accidente->Estatus = $estatus;

        $fecha_fin = Carbon::now()->format('Y-m-d');
        $hora_fin = Carbon::now()->format('H:i:s');
        if($estatus === 4){
            $accidente->FechaFin = $fecha_fin;
            $accidente->HoraFin = $hora_fin;
        }

        if($estatus === 1){
            $accidente->FechaFin = null;
            $accidente->HoraFin = null;
        }

        $accidente->save();
        
        return redirect()->route('accidentes.index')->with('success', 'El estado del accidente se ha actualizado correctamente.');
        



    }


    public function generarpdf(Request $request){

        //OBTENER USUARIO y su ID
        $usuario = auth()->user();
        $id_empresa = $usuario->id_empresa;

        //OBTENER SU EMPRESA
        $empresa = Empresa::find($id_empresa);


        //OBTENER ACCIDENTE
        $id_accidente = $request->input('id');
        // $accidente = TblAccidente::find($id_accidente);
        $accidente = TblAccidente::where('Folio', $id_accidente)->first();
       

   

        $dompdf = new Dompdf();
        $html = view('accidentes.accidentepdf', ['accidente' => $accidente], ['empresa' => $empresa])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->stream(); 

        
      
         return $id_accidente;
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function mostrar($id, $tipo)
    {
        $accidente = DB::connection('mysql2')
        ->table('tblaccidentes')
        ->where('Folio', '=', $id)

        ->where('Tipo', '=', $tipo)
    ->orderBy('Folio', 'DESC')
    ->first();
        $unidades = Unidad::all();
        $rutas = Ruta::all();
        $empleados = Empleado::where('Estatus', 'A')
                    ->where('IdPuesto', 1)
                    ->orderBy('Nombre', 'asc')
                    ->get();

        $causales = catcausalesacc::all();
        $user = Auth::user();
    //  dd($accidente);

        return view('accidentes.actualizar', compact('accidente','unidades', 'rutas', 'empleados', 'causales','user'));
    }

    public function actualizar(Request $request, $id, $tipo)
    {
    //    dd($request);
        $accidente = DB::connection('mysql2')
        ->table('tblaccidentes')
        ->where('Folio', '=', $id)
        ->where('Tipo', '=', $tipo)
        ->first(); // Esto ejecuta la consulta y obtiene el primer registro encontrado

    // Verifica que se haya encontrado el registro
    if ($accidente) {
        // Realiza la actualización
        DB::connection('mysql2')
            ->table('tblaccidentes')
            ->where('Folio', '=', $id)
            ->where('Tipo', '=', $tipo)
            ->update([
                'Fecha' => $request->input('dg_fecha'),
                // 'Hora' => $request->input('dg_hora'),
                'Lugar' => $request->input('dg_lugarAccidente'),
               
                'Descripcion' => $request->input('dg_descripcionAccidente'),
                // 'ot_lesionadosCheck' => $request->input('ot_lesionadosCheck'),
                'NomLesionados' => $request->input('ot_lesionadosExtra'),
                // 'ot_datosAseguradoraCheck' => $request->input('ot_datosAseguradoraCheck'),
                'Ajustador' => $request->input('ot_ajustador'),
                'NumCQ' => $request->input('ot_numeroCq'),
                'CostoAprox' => $request->input('ot_costoAproximado'),
            
                'Perito' => $request->input('ot_perito'),
                'Gerente' => $request->input('ot_gerente'),
                'Observ' => $request->input('ot_observaciones'),
            ]);

            return redirect()->route('accidentes.index')->with('success', 'El accidente se ha Complementado correctamente.');
    } else {
        // Si no se encuentra el registro, manejar el caso
        return redirect()->back()->with('error', 'Registro no encontrado.');
    }
    }

    public function getZonasPorRuta($idRuta)
{
    $zonas = \App\Models\CatzonaRuta::where('IdRuta', $idRuta)
                ->select('IdZona', 'Zona')
                ->get();

    return response()->json($zonas);
}
   
}




