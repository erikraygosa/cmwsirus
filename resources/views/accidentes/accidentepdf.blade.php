

@php 


function ordenarString($string, $cantidadDeEspaciosEsperados) {
    $tamañoDelString = strlen($string);
    $espacios = $cantidadDeEspaciosEsperados - $tamañoDelString;
    return $string . str_repeat('_', $espacios);
}



function obtenerEstado($estado){
  $estadoNombre = "";
  switch($estado){
    case 1: 
      $estadoNombre = "ABIERTO";
      break;

      case 2:
        $estadoNombre = "PROCESANDO";
        break;

        case 3:
          $estadoNombre = "PRESUPUESTADO";
          break;

          case 4:
            $estadoNombre = "CERRADO";
            break;
  }

  return $estadoNombre;
}


function obtenerCargoCostos($cargarCostosA){
  $cargoCostos = "";
  switch($cargarCostosA){
    case 1: 
      $cargoCostos = "Operador";
      break;

      case 2:
        $cargoCostos = "Tercero Externo";
        break;

        case 3:
          $cargoCostos = "Costos de operacion";
          break;

          case 4:
            $cargoCostos = "Tercero interno";
            break;
  }

  return $cargoCostos;
}




$empresaNombre = $empresa->Nombre;


$folio = $accidente->Folio;
$fechaInicio = $accidente->FechaIni;
$fechaFin = $accidente->FechaFin;


$estatus = obtenerEstado($accidente->Estatus);





$lugar = ordenarString($accidente->Lugar, 50);
$fecha = $accidente->Fecha;
$hora = $accidente->Hora;


$descripcionAccidente = $accidente->Descripcion;

//PROPIETARIO
$propietario = ordenarString($accidente->Propietario, 56);
$telefonoPropietario = $accidente->TelProp;

//CONDUCTOR
$conductor = ordenarString($accidente->Conductor, 56);
$telefonoConductor = $accidente->TelConduc;

//DATOS TERCERO
$marcaTercero = $accidente->Marca1;
$modeloTercero = $accidente->Modelo1;
$añoTercero = $accidente->Anio1;
$colorTercero = $accidente->Color1;
$placasTercero = $accidente->Placas1;

$dañosTercero = $accidente->DaniosTercero;;

//DATOS DE LA UNIDAD
$unidad = $accidente->Unidad;
$unidadId = $accidente->IdUnidad;
$unidadRuta = $accidente->Ruta;
$unidadOperador = $accidente->Operador;
$unidadLicencia = $accidente->Licencia;

$unidadMarca = $accidente->Marca2;
$unidadModelo =  $accidente->Modelo2;
$unidadAño =  $accidente->Anio2;
$unidadColor = $accidente->Color2;
$unidadPlacas = $accidente->Placas2;

$unidadDaños = $accidente->DaniosUnidad;


//OTROS DATOS 
$lesionados = ($accidente->Lesionados == "S") ? "SI": "NO";
$nombres = $accidente->NomLesionados;

//OTROS DATOS2 
$aseguradora = ($accidente->Aseguradora == "S") ? "SI" : "NO";
$ajustador = $accidente->Ajustador;
$numCQ = $accidente->NumCQ;
$costoAprox = $accidente->CostoAprox;
($accidente->SSP == "S") ? "SI" : "NO";
$ssp = ($accidente->SSP == "S") ? "SI" : "NO";
$perito = $accidente->Perito;
$costoReal = $accidente->CostoReal;


($accidente->Responsable == "S") ? "SI" : "NO";
$responsable = ($accidente->Responsable == "S") ? "SI" : "NO";
$cargarCostosA = obtenerCargoCostos($accidente->CargoCostos);
$gerente = $accidente->Gerente;

$observaciones = $accidente->Observ;





@endphp















<!DOCTYPE html>
<html>
<head>
	<title>Reporte de Accidentes</title>
	<style type="text/css">


  
		.bold {
			font-weight: bold;
            font-size: 13px;
		}
		.center {
			text-align: center;
		}
		table {
			border-collapse: collapse;
			width: 100%;
           
		}
		td, th {
			border: 1px solid black;
			padding: 1px;
			text-align: left;
         
		}

    


    .centeredTable td,
  .centeredTable th {
   border: 1px solid black;
  padding: 3px;
  text-align: center;
  }

  .tableSetted td,
  .tableSetted th {

  padding: 0px;

  }


    .underlined {
  text-decoration: underline;
  /* La longitud de la línea de subrayado puede ser ajustada según sea necesario */
  text-decoration-color: black;
  /* El color de la línea de subrayado puede ser ajustado según sea necesario */
  text-decoration-thickness: 2px;
  /* El grosor de la línea de subrayado puede ser ajustado según sea necesario */
}







/* WRAPER */





   


	</style>
</head>
<body>
	<h1 class="center bold" style="margin-top: -25px;">{{$empresaNombre}}</h1>
	<h2 class="center bold">REPORTE DE ACCIDENTES</h2>
	<table style="border:none; width: 70%;">
		<thead style="border:none">
			<tr style="border:none">
				<th class="bold" style="border:none; margin-bottom: -5px" >Folio:</th>
				<th class="bold" style="border:none; margin-bottom: -5px">Inicia:</th>
				<th class="bold" style="border:none; margin-bottom: -5px">Finaliza:</th>
				<th class="bold" style="border:none; margin-bottom: -5px">Estatus:</th>

                      
			</tr>
		</thead>
		<tbody style="border:none">
			<tr style="border:none">
				<td style="border:none; ">{{$folio}}</td>
				<td style="border:none;">{{$fechaInicio}}</td>
				<td style="border:none;">{{$fechaFin}}</td>
				<td style="border:none;">{{$estatus}}</td>


   
			</tr>
		</tbody>
	</table>


  <div class="contenedor" style="margin-top: 2px;">
   <b> Lugar: </b> <span><u>{{$lugar}}</u> </span>
    <span style="margin-left: 50px;"><b>Fecha:</b> <u>{{$fecha}}</u></span> 
    <span style="margin-left: 20px;"><b>Hora:</b> <u>{{$hora}}</u></span>
</div>

<div class="contenedor" style="margin-top: 8px;">
<label style="font-weight: bold; ">Descripción del Accidente:</label>
<textarea  >{{$descripcionAccidente}}</textarea>
</div>





<style>

.title{
    padding-left:5px;
}

.up{
    margin-top:-3px;
}



</style>

<!-- Datos del tercero -->
<div style="border: 1px solid; margin-top: 10px;"> <h3 class="title up bold">Datos del tercero</h3>
 
  <p class="title up" style="font-size: 13px"><b>Propietario:</b> <u>{{$propietario}}</u><b>Telefono:</b>{{$telefonoPropietario}}</p>
  <p class="title up" style="font-size: 13px"><b>Conductor:</b> <u>{{$conductor}}</u><b>Telefono:</b>{{$telefonoConductor}}</p>

  <table style="border-collapse: collapse;" class="centeredTable">
    <tr style="font-size: 13px">
      <th>Marca</th>
      <th>Modelo</th>
      <th>Año</th>
      <th>Color</th>
      <th>Placas</th>
    </tr>
    <tr style="font-size: 13px">
      <td>{{$marcaTercero}}</td>
      <td>{{$modeloTercero}}</td>
      <td>{{$añoTercero}}</td>
      <td>{{$colorTercero}}</td>
      <td>{{$placasTercero}}</td>
    </tr>
  </table>
  
  <p class="title up" style="font-size: 13px; margin-top: 1px;"><b>Daños:</b></p>
  <textarea class="up" style="border: 1px solid black; font-family: Arial; font-size: 12px;" rows="3">{{$dañosTercero}}</textarea>
</div>








<!-- Datos de la unidad -->
<div style="border: 1px solid; margin-top: 10px;"> <h3 class="title up bold">Datos de la unidad</h3>
 
  <p class="title up " style="font-size: 13px"><b>Unidad:</b> <span class="underlined">{{$unidad}}</span><b> ID:</b>  <span class="underlined">{{$unidadId}}  </span>  <b> Ruta:</b> <span class="underlined">{{$unidadRuta}}</span></p></p>
  <p class="title up" style="font-size: 13px"><b>Operador:</b> <span class="underlined">{{$unidadOperador}} </span><b>Licencia: </b><span class="underlined">{{$unidadLicencia}}</span></p>

  <table style="border-collapse: collapse;" class="centeredTable">
    <tr style="font-size: 13px">
      <th>Marca</th>
      <th>Modelo</th>
      <th>Año</th>
      <th>Color</th>
      <th>Placas</th>
    </tr>
    <tr style="font-size: 13px">
      <td>{{$unidadMarca}}</td>
      <td>{{$unidadModelo}}</td>
      <td>{{$unidadAño}}</td>
      <td>{{$unidadColor}}</td>
      <td>{{$unidadPlacas}}</td>
    </tr>
  </table>
  
  <p class="title up" style="font-size: 13px; margin-top: 1px;"><b>Daños:</b></p>
  <textarea class="up" style="border: 1px solid black; font-family: Arial; font-size: 12px;" rows="3">{{$unidadDaños}}</textarea>
</div>




<table style="width: 100%; margin-top: 5px;" >
  <tr>
    <td style="width: 40%; vertical-align: top;">
    <p><b>Lesionados:</b> {{$lesionados}}</p>
        <p><b>Nombres:</b></p>
       <p>{{$nombres}}</p>


   
    </td>



    </div>
    <td style="width: 60%; vertical-align: top; border:none;">


    <div class="box" style="border: 1px solid black; margin-left: 20px">
    <b> <span>Aseguradora:</b> {{$aseguradora}}</span><br>
    <b><span>Ajustador:</b> {{$ajustador}}</span><br>
    <b><span>Numero (CQ):</b> {{$numCQ }}</span><br>
    <b><span>Costo aprox:</b> {{$costoAprox}}</span><br>
</div>

<div class="box" style="border: 1px solid black; margin-top: 15px; margin-left: 20px">
  <b><span>S.S.P:</b> {{$ssp}}</span><br>
  <b><span>Perito:</b> {{$perito}}</span><br>
  <b><span>Costo real:</b> {{$costoReal}}</span><br>
</div>


<div class="box" style="border: 1px solid black; margin-top: 15px; margin-left: 20px">
<b><span>Responsable:</b> {{$responsable}}</span><br>
<b><span>Cargar costos a:</b> {{$cargarCostosA}}</span><br>
<b><span>Gerente:</b> {{$gerente}}</span><br>
</div>

<div class="box" style="margin-top:15px; margin-left:20px">
<b><span>Observaciones:</b></span><br>
<textarea style="border: 1px solid black; font-family: Arial; font-size: 12px;resize:vertical;overflow: auto;min-height: 60px;" id="tObservaciones" >{{$observaciones}}</textarea>


</div>




    </td>
  </tr>



  <tr>
    <td style="width: 50%; vertical-align: top; border:none;">
    <br>
       <div class="center">
    <p><b>FIRMA DELEGADO</b></p>
    <p>_____________________________</p>
      
      
    </td>



    </div>
    <td style="width: 50%; vertical-align: top; border:none;">
       <br>
       <div class="center">
    <p><b>FIRMA OPERADOR</b></p>
    <p>_____________________________</p>
      
    </td>
  </tr>


</table>



</body>
</html>