@extends('adminlte::page')

@section('title', 'Complementar')

@section('content_header')
    <h1>Complementar</h1>
@stop

@section('content')
    <p>Complementar Accidentes</p>                           

    <form id="form-accidente" method="POST" action="{{ route('registro.actualizar', ['id' => $accidente->Folio, 'tipo' => $accidente->Tipo]) }}">
    @csrf
    <label>Accidente:</label>
    <div class="row align-items-start ">
    
    <div class="col mb-3">
    </div>
    </div>
    <div class="d-flex justify-content-end">
    <button type="submit" class="btn btn-success float-right mr-3">Aceptar</button>
    <a href="{{ route('accidentes.index') }}" class="btn btn-danger float-right">Cancelar</a>

   
   
    
</div>





<!-- Nav tabs -->
<ul class="nav nav-tabs" id="myTab" role="tablist">
  <li class="nav-item">
    <a class="nav-link active" id="tab-datosGenerales" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Datos generales</a>
  </li>
  <!-- <li class="nav-item">
    <a class="nav-link" id="tab-datosUnidad" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">Datos de la unidad</a>
  </li>
  <li class="nav-item">
    <a class="nav-link" id="tab-datosTerceros" data-toggle="tab" href="#contact3" role="tab" aria-controls="contact" aria-selected="false">Datos del tercero</a>
  </li> -->

  <li class="nav-item">
    <a class="nav-link" id="tab-otrosDatos" data-toggle="tab" href="#contact4" role="tab" aria-controls="contact" aria-selected="false">Otros datos</a>
  </li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
  <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
    
  
 
  <div class="form-group row">
    <div class="col-sm-2">
      <label for="dg_fecha" class="col-form-label">Fecha</label>
      <input type="date" value="{{$accidente->FechaIni}}" class="form-control" id="dg_fecha" name="dg_fecha">
    </div>
    <!-- <div class="col-sm-2">
      <label for="dg_hora" class="col-form-label">Hora</label>
      <input type="time" value="{{$accidente->HoraIni}}" class="form-control" id="dg_hora" name="dg_hora">
    </div> -->
    <div class="col-sm-8">
      <label for="dg_lugarAccidente" class="col-form-label">Lugar del accidente</label>
      <input type="text" value="{{$accidente->Lugar}}" class="form-control" id="dg_lugarAccidente" name="dg_lugarAccidente">
    </div>
  </div>
  <div class="form-group row">
  <!-- <div class="col-sm-4">
    <label for="fecha" class="col-sm-6 col-form-label">Causal del Accidente</label>
    <select class="form-control" name="ConcepCau" id="ConcepCau">
      <option value="{{$accidente->IdCau}}">{{$accidente->ConcepCau}}</option>
      @foreach ($causales as $causale)
    <option value="{{$causale->IdCau}}">{{$causale->Concepto}}</option>

    @endforeach
    </select>
  </div> -->
  </div>

  <div class="form-group row">
    <div class="col-sm-12">
      <label for="dg_descripcionAccidente" class="col-form-label">Descripción del accidente</label>
      <textarea class="form-control form-control-lg" rows="5" value="{{$accidente->Descripcion}}" name="dg_descripcionAccidente" id="dg_descripcionAccidente">{{$accidente->Descripcion}}</textarea>
    </div>
  </div>

  <!-- <div class="container"> -->
    <!-- <label>Cargar costos a:</label>
    <div class="row align-items-start">
      <div class="col">
        <div class="btn-group btn-group-toggle" data-toggle="buttons">
          <label class="btn btn-outline-secondary">
            <input type="radio" name="dg_radioCargarCostos" id="dg_radioCargarCostos1" autocomplete="off" value="1"> Operador
          </label>
          <label class="btn btn-outline-secondary">
            <input type="radio" name="dg_radioCargarCostos" id="dg_radioCargarCostos2" autocomplete="off" value="2"> Tercero externo
          </label>
          <label class="btn btn-outline-secondary">
            <input type="radio" name="dg_radioCargarCostos" id="dg_radioCargarCostos3" autocomplete="off" value="3"> Costos de operación
          </label>
          <label class="btn btn-outline-secondary">
            <input type="radio" name="dg_radioCargarCostos" id="dg_radioCargarCostos4" autocomplete="off" value="4"> Tercero interno
          </label>
          <label class="btn btn-outline-secondary">
            <input type="radio" name="dg_radioCargarCostos" id="dg_radioCargarCostos5" autocomplete="off" value="5"> RC viajero
          </label>
          <label class="btn btn-outline-secondary">
            <input type="radio" name="dg_radioCargarCostos" id="dg_radioCargarCostos6" autocomplete="off" value="6"> Imagen
          </label>
        </div>
      </div> -->
      <!-- <div class="col">
        <div class="form-check ml-auto">
          <input class="form-check-input" type="checkbox" value="s" name="dg_responsable" id="dg_responsable">
          <label class="form-check-label" for="dg_responsable">
            El operador es responsable
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="dg_presupuestar" value="s" id="dg_presupuestar">
          <label class="form-check-label" for="dg_presupuestar">
            Presupuestar accidente
          </label>
        </div>
      </div>
      <div class="col">
        <label>Forma de pago del Tercero Externo:</label>
          <div class="row align-items-start">
            <div class="col">
              <div class="btn-group btn-group-toggle" data-toggle="buttons">
                  <label class="btn btn-outline-secondary">
                    <input type="radio" name="FormaPagoTer" id="FormaPagoTer" autocomplete="off" value="E"> Efectivo
                  </label>
                  <label class="btn btn-outline-secondary">
                    <input type="radio" name="FormaPagoTer" id="FormaPagoTer" autocomplete="off" value="V"> Vales
                  </label>
                  
                </div>
              </div>
           </div>
      </div> -->

   
  </div>


  <div class="tab-pane fade" id="contact1" role="tabpanel" aria-labelledby="contact-tab1">




<br>
</div>
  <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
 
  <!--TAB2-->
  <label>Datos de la unidad</label>

<div class="form-group row">

<div class="col-sm-3">
    <label for="fecha" class="col-sm-2 col-form-label">Unidad</label>
    <select class="form-control" name="du_unidad" id="du_unidad">
        <option value="">Selecciona una opción</option>
        @foreach ($unidades as $unidad)
        <option value="{{ $unidad->IdUnidad }}" 
                data-marca="{{ $unidad->Marca }}" 
                data-modelo="{{ $unidad->Modelo }}" 
                data-año="{{ $unidad->Anio }}"
                data-color="{{ $unidad->Color }}" 
                data-placas="{{ $unidad->Placas }}">
            {{ $unidad->Unidad }} 
        </option>
        @endforeach
    </select>
</div>

  <div class="col-sm-3">
    <label for="fecha" class="col-sm-2 col-form-label">Turno</label>
    <select class="form-control" name="du_turno" id="select-example">
      <option value="">Selecciona una opción</option>
      <option value="AM">AM</option>
      <option value="PM">PM</option>

    </select>
  </div>

  <div class="col-sm-6">
    <label for="fecha" class="col-sm-3 col-form-label">Ruta</label>
    <select class="form-control" name="du_ruta" id="select-example">
      <option value="">Selecciona una opción</option>
      @foreach ($rutas as $ruta)
    <option value="{{$ruta->IdRuta}}">{{$ruta->Ruta}}</option>

    @endforeach
    </select>
  </div>

</div>


<div class="form-group row">

  <div class="col-sm-4">
    <label for="fecha" class="col-sm-4 col-form-label">Operador</label>
    <select class="form-control" name="du_operador" id="du_oper">
      <option value="">Busqueda de Operador</option>
      @foreach ($empleados as $empleado)
    <option value="{{$empleado->IdEmpleado}}">{{$empleado->Nombre}}</option>

    @endforeach
    </select>
  </div>

  <div class="col-sm-3">
    <label for="fecha" class="col-sm-2 col-form-label ml-5">Licencia</label>
    <input type="text" class="form-control ml-5" id="fecha" name="du_licencia">
  </div>

</div>
<label>Sindicalizado:</label>
   
    <div class="col sm-5">
      <div class="btn-group btn-group-toggle mb-3" data-toggle="buttons">
          <label class="btn btn-outline-secondary">
            <input type="radio" name="sindicalizado" id="hh_AccidenteInterno" autocomplete="off" value="I"> Si
          </label>
          <label class="btn btn-outline-secondary">
            <input type="radio" name="sindicalizado" id="hh_AccidenteExterno" autocomplete="off" value="E"> NO
          </label>
   
          <label for="fecha" class="col-sm-3 col-form-label">Sindicato</label>
          <select class="form-control" name="Sindicato" id="Sindicato">
            <option value="">Selecciona una opción</option>
              <option value="1">Canek</option>
              <option value="2">Vargas</option>

   
          </select>
         
        </div>
      </div>


<div class="form-group row">
  <div class="col-sm-2">
    <label for="fecha" class="col-sm-2 col-form-label">Marca</label>
    <input type="text" class="form-control" id="du_marca" name="du_marca">
  </div>
  <div class="col-sm-3">
    <label for="fecha" class="col-sm-3 col-form-label">Modelo</label>
    <input type="text" class="form-control" id="du_modelo" name="du_modelo">
  </div>
  <div class="col-sm-2 ml-5">
    <label for="fecha" class="col-sm-2 col-form-label" >Año</label>
    <input type="text" class="form-control" id="du_año" name="du_año" maxlength="4">
  </div>
</div>


<div class="container">
  <div class="row">
    <div class="col-md-3">
      <div class="form-group">
        <label for="color">Color:</label>
        <input type="text" class="form-control" name="du_color" id="du_color">
      </div>

      <div class="mr-5">
        <div class="form-group">
          <label for="placas">Placas:</label>
          <input type="text" class="form-control" name="du_placas" id="du_placas">
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label for="danos">Daños:</label>
        <textarea class="form-control" id="du_danos" name="du_danos" rows="6"></textarea>
      </div>
    </div>
  </div>
</div>



  </div>
  
  <div class="tab-pane fade" id="contact3" role="tabpanel" aria-labelledby="contact-tab3">

  <!--TAB3-->

  <label>Datos de la unidad</label>

  <div class="form-check">
  <input class="form-check-input" type="checkbox" value="on" name="dt_check" id="checkDatosTercero">
  <label class="form-check-label" for="datos-tercero">
    Datos del tercero
  </label>
</div>


<div class="form-group row">

 

  <div class="col-sm-6">
    <label for="fecha" class="col-sm-2 col-form-label">Propietario</label>
    <input type="text" class="form-control"  disabled id="dt_propietario" name="dt_propietario">
  </div>

  <div class="col-sm-3">
    <label for="fecha" class="col-sm-3 col-form-label">Telefono</label>
    <input type="number" class="form-control" disabled id="dt_telefonoPropietario" name="dt_telefonoPropietario">
  </div>

</div>


<div class="form-group row">

 

  <div class="col-sm-6">
    <label for="fecha" class="col-sm-2 col-form-label">Conductor</label>
    <input type="text" class="form-control" disabled id="dt_conductor" name="dt_conductor">
  </div>

  <div class="col-sm-3">
    <label for="fecha" class="col-sm-3 col-form-label">Telefono</label>
    <input type="number" class="form-control" disabled id="dt_telefonoConductor" name="dt_telefonoConductor">
  </div>

</div>




<div class="form-group row">
  <div class="col-sm-2">
    <label for="fecha" class="col-sm-2 col-form-label">Marca</label>
    <input type="text" class="form-control" disabled id="dt_marca" name="dt_marca">
  </div>
  <div class="col-sm-2">
    <label for="fecha" class="col-sm-2 col-form-label">Modelo</label>
    <input type="text" class="form-control" disabled id="dt_modelo" name="dt_modelo">
  </div>
  <div class="col-sm-2 ml-5">
    <label for="fecha" class="col-sm-2 col-form-label">Año</label>
    <input type="text" class="form-control" disabled id="dt_año" name="dt_año" maxlength="4">
  </div>
</div>

<div class="container">
  <div class="row">
    <div class="col-md-3">
      <div class="form-group">
        <label for="color">Color:</label>
        <input type="text" class="form-control" disabled name="dt_color" id="dt_color">
      </div>

      <div class="mr-5">
        <div class="form-group">
          <label for="placas">Placas:</label>
          <input type="text" class="form-control" disabled name="dt_placas" id="dt_placas">
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label for="danos">Daños:</label>
        <textarea class="form-control" id="dt_daños" disabled name="dt_daños" rows="6"></textarea>
      </div>
    </div>
  </div>
</div>



  </div>

  <div class="tab-pane fade" id="contact4" role="tabpanel" aria-labelledby="contact-tab4">

  <!--TAB 4-->

  <div class="row">
  <div class="col-md-6">
    <div class="form-group">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="on"  name="ot_lesionadosCheck" id="ot_lesionadosCheck">
        <label class="form-check-label" for="lesionadosCheck">
          Lesionados
        </label>
      </div>
      <textarea class="form-control" id="ot_lesionadosExtra" value="{{$accidente->NomLesionados}}" disabled name="ot_lesionadosExtra" rows="12">{{$accidente->NomLesionados}}</textarea>
    </div>
  </div>
  <div class="col-md-6">
    <div class="form-group">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" value="on" name="ot_datosAseguradoraCheck" id="ot_datosAseguradora">
        <label class="form-check-label" for="aseguradoraCheck">
          Datos de la aseguradora
        </label>
      </div>
      <div class="row">
        <div class="col-md-6">
          <div class="form-group">
            <label for="ajustadorInput">Ajustador</label>
            <input type="text" class="form-control" value="{{$accidente->Ajustador}}" disabled name="ot_ajustador" id="ot_ajustador">
          </div>
          <div class="form-group">
            <label for="cqInput">Número (CQ)</label>
            <input type="text" class="form-control" value="{{$accidente->NumCQ}}" disabled name="ot_numeroCq" id="ot_numeroCq">
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-group">
            <label for="costoInput">Costo aproximado</label>
            <input type="number" class="form-control" value="{{$accidente->CostoAprox}}" disabled name="ot_costoAproximado" id="ot_costoAproximado">
          </div>
        </div>
      </div>
      <div class="form-group">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="on" name="ot_sspCheck" id="ot_ssp">
          <label class="form-check-label" for="sspCheck">
            S.S.P.
          </label>
        </div>
        <div class="form-group">
          <label for="peritoInput">Perito</label>
          <input type="text" class="form-control" value="{{$accidente->Perito}}" name="ot_perito" disabled id="ot_perito">
        </div>
        <div class="form-group">
          <label for="gerenteInput">Gerente</label>
          <input type="text" class="form-control" name="ot_gerente" id="ot_gerente" value="{{$accidente->Gerente}}">
        </div>
        <div class="form-group">
          <label for="observacionesTextarea">Observaciones</label>
          <textarea class="form-control" id="ot_observaciones" name="ot_observaciones" value="{{$accidente->Observ}}" rows="2">{{$accidente->Observ}}</textarea>
        </div>
      </div>


  </div>

</div>
</div>
</form>




@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
  
@stop

@section('js')




<script>
    $(document).ready(function() { $("#du_oper").select2(); });
    // $(document).ready(function() { $("#du_unidad").select2(); });
</script>


  <script>  




//CONTROLADOR
//TAB3


function comprobarSeleccionTipoAccidente() {
  var accidenteInterno = document.querySelector('#hh_AccidenteInterno');
  var accidenteExterno = document.querySelector('#hh_AccidenteExterno');
  
//   if (!accidenteInterno.checked && !accidenteExterno.checked) {
//     alert('Por favor, seleccione un tipo de accidente.');
//     return false;
//   }
  
  return true;
}



 var sspCheck = document.querySelector('#ot_ssp');

 sspCheck.addEventListener("change", function(){

  var perito = document.querySelector('#ot_perito');

  if(sspCheck.checked){
    perito.disabled=false;
  } else {
    perito.disabled=true;
  }

 });


  var otAseguradoraCheck = document.querySelector('#ot_datosAseguradora');

  otAseguradoraCheck.addEventListener("change", function (){

    var otAjustador = document.querySelector('#ot_ajustador');
  var otCostoAproximado = document.querySelector('#ot_costoAproximado');

  var numeroCQ = document.querySelector('#ot_numeroCq');

  if(otAseguradoraCheck.checked){
    otAjustador.disabled = false;
    otCostoAproximado.disabled = false;
    numeroCQ.disabled = false;

  } else {
    otAjustador.disabled = true;
    otCostoAproximado.disabled = true;
    numeroCQ.disabled = true;
  }

  });

 
  document.addEventListener('DOMContentLoaded', function() {
        var unidadSelect = document.getElementById('du_unidad');

        unidadSelect.addEventListener('change', function() {
            var selectedOption = unidadSelect.options[unidadSelect.selectedIndex];
            var marca = selectedOption.getAttribute('data-marca');
            var modelo = selectedOption.getAttribute('data-modelo');
            var año = selectedOption.getAttribute('data-año');
            var color = selectedOption.getAttribute('data-color');
            var placas = selectedOption.getAttribute('data-placas');

            document.getElementById('du_marca').value = marca;
            document.getElementById('du_modelo').value = modelo;
            document.getElementById('du_año').value = año;
            document.getElementById('du_color').value = color;
            document.getElementById('du_placas').value = placas;
        });
    });








  //LESIONADOS CALLBACK
  var otCheck = document.querySelector('#ot_lesionadosCheck');
  otCheck.addEventListener("change", function(){
    var otTextarea = document.querySelector('#ot_lesionadosExtra');
    if (otCheck.checked) {
    otTextarea.disabled = false;
  } else {
    otTextarea.disabled = true;
  }

  });


  function comprobarOtrosDatos(){

    var valido = true;

    var otCheck1 = document.querySelector('#ot_lesionadosCheck');
    var otTextarea1 = document.querySelector('#ot_lesionadosExtra').value;
    var tabOtrosdatos = document.querySelector("#tab-otrosDatos"); // Does not match anything

    var otCheckDatosAseguradora = document.querySelector('#ot_datosAseguradora');
    var otAjustador = document.querySelector('#ot_ajustador').value;
    var otCostoAproximado = document.querySelector('#ot_costoAproximado').value;
    var otNumeroCQ = document.querySelector('#ot_numeroCq').value;

    var otCheckSSP = document.querySelector('#ot_ssp');
    var otPerito = document.querySelector('#ot_perito').value;

    var otGerente = document.querySelector('#ot_gerente').value;
    var otObservaciones = document.querySelector('#ot_observaciones').value;


    if(otCheck1.checked){

      if(otTextarea1 == ''){
        valido = false;
        alert("No puede dejar el campo vacio en otros datos");

       tabOtrosdatos.click();        

      }


    }


    if(otCheckDatosAseguradora.checked){

      if(otAjustador == '' || otCostoAproximado == '' || otNumeroCQ == ''){
        valido = false;
        alert("No se puede dejar el campo vacio en otros datos");
        tabOtrosdatos.click();        
      }

    }


    if(otCheckSSP.checked){

      if(otPerito == ''){
        alert("No se puede dejar el campo vacio en otros datos");
        valido = false;
        tabOtrosdatos.click(); 
      }
    }

    if(otGerente == '' || otObservaciones == ''){
      alert("No se puede dejar el campo vacio en otros datos");
      valido = false;
        tabOtrosdatos.click(); 
    }



    return valido;
  }

  






function deshabilitarCamposTab3() {
  document.getElementById("dt_propietario").disabled = true;
  document.getElementById("dt_conductor").disabled = true;
  document.getElementById("dt_marca").disabled = true;
  document.getElementById("dt_modelo").disabled = true;
  document.getElementById("dt_año").disabled = true;
  document.getElementById("dt_color").disabled = true;
  document.getElementById("dt_placas").disabled = true;
  document.getElementById("dt_daños").disabled = true;
  document.getElementById("dt_telefonoPropietario").disabled = true;
  document.getElementById("dt_telefonoConductor").disabled = true;
}


function habilitarCamposTab3() {

  document.getElementById("dt_telefonoPropietario").disabled = false;
  document.getElementById("dt_telefonoConductor").disabled = false;

  document.getElementById("dt_propietario").disabled = false;
  document.getElementById("dt_conductor").disabled = false;
  document.getElementById("dt_marca").disabled = false;
  document.getElementById("dt_modelo").disabled = false;
  document.getElementById("dt_año").disabled = false;
  document.getElementById("dt_color").disabled = false;
  document.getElementById("dt_placas").disabled = false;
  document.getElementById("dt_daños").disabled = false;
}










function checkDatosGenerales(){
  var valido = true;
  var tabDatosGenerales = document.querySelector("#tab-datosGenerales"); // Does not match anything
  var campo1 = $('#dg_fecha').val();
  var campo2 = $('#dg_hora').val();
  var campo3 = $('#dg_descripcionAccidente').val();
  var campo4 = $('#dg_radioCargarCostos').val();
  var campo5 = $('#dg_lugarAccidente').val();
  var campo6 = $('input[name="dg_radioCargarCostos"]:checked').val();

//   if (campo1 === '' || campo2 === '' || campo3 === '' || campo4 === '' || campo5 === '' || !campo6) {
//     // Si algún campo está vacío, mostrar un mensaje de error
//     valido = false;
//     alert('Por favor, rellena todos los campos antes de continuar.');
//     tabDatosGenerales.click();
//   } else {
//     // Todos los campos están llenos, continuar con la acción
    
   
//   }


  return valido;
}

function checkDatosUnidad(){
  var valido = true;

  var tabDatosUnidad = document.querySelector("#tab-datosUnidad"); // Does not match anything
  var unidad = $('select[name=du_unidad]').val();
    var turno = $('select[name=du_turno]').val();
    var ruta = $('select[name=du_ruta]').val();
    var operador = $('select[name=du_operador]').val();
    var licencia = $('input[name=du_licencia]').val();
    var marca = $('input[name=du_marca]').val();
    var modelo = $('input[name=du_modelo]').val();
    var año = $('input[name=du_año]').val();
    var color = $('input[name=du_color]').val();
    var placas = $('input[name=du_placas]').val();
    var danos = $('textarea[name=du_danos]').val();

    // Verifica si se han completado todos los campos
    // if (unidad == '' || turno == '' || ruta == '' || operador == '' || licencia == '' || marca == '' || modelo == '' || año == '' || color == '' || placas == '' || danos == '') {
    //   // Si no se han completado todos los campos, muestra un mensaje de error
    //   alert('Por favor complete todos los campos antes de enviar el formulario.');
    //   valido = false;
    //   tabDatosUnidad.click();
    // }

return valido;

}

function checkDatosTercero(){
  var tabdatosTercero = document.querySelector('#tab-datosTerceros');
  var checkTab3 = document.querySelector('#checkDatosTercero');

  var valido = true;
  const dt_propietario = document.getElementById("dt_propietario").value;
  const dt_telefono_propietario = document.getElementById("dt_telefonoPropietario").value;
  const dt_telefono_conductor = document.getElementById("dt_telefonoConductor").value;
  const dt_conductor = document.getElementById("dt_conductor").value;
  const dt_marca = document.getElementById("dt_marca").value;
  const dt_modelo = document.getElementById("dt_modelo").value;
  const dt_año = document.getElementById("dt_año").value;
  const dt_color = document.getElementById("dt_color").value;
  const dt_placas = document.getElementById("dt_placas").value;
  const dt_daños = document.getElementById("dt_daños").value;


//   if(checkTab3.checked){
    
//     if(dt_propietario == '' || dt_telefono_propietario == '' || dt_telefono_conductor == '' || dt_conductor == '' || dt_marca == '' || dt_modelo == '' || dt_año == '' || dt_color == '' || dt_placas == '' || dt_daños == ''){
//       alert("Hacen falta datos por llenar en datos del tercero");
//       valido = false;
//       tabdatosTercero.click();
//     }

//   }

  return valido;
}



//CONTROLAR EL TERCER PANEL
// Obtener los valores de los campos
const checkDatosTercero1 = document.getElementById("checkDatosTercero");
const dt_propietario = document.getElementById("dt_propietario");
const dt_telefono_propietario = document.getElementById("dt_telefonoPropietario");
const dt_telefono_conductor = document.getElementById("dt_telefonoConductor");
const dt_conductor = document.getElementById("dt_conductor");
const dt_marca = document.getElementById("dt_marca");
const dt_modelo = document.getElementById("dt_modelo");
const dt_año = document.getElementById("dt_año");
const dt_color = document.getElementById("dt_color");
const dt_placas = document.getElementById("dt_placas");
const dt_daños = document.getElementById("dt_daños");

// Escuchar los eventos para mostrar o esconder los campos de datos del tercero
checkDatosTercero1.addEventListener("change", function() {
  if (checkDatosTercero1.checked) {
    habilitarCamposTab3();
  } else {
    deshabilitarCamposTab3();
  }
});

// Obtener los valores de los campos






//setInterval(checkDatosGenerales, 4000);

$(document).ready(function() {
  // Escuchar el evento submit del formulario
  $('#form-accidente').submit(function(event) {
    // Prevenir el comportamiento por defecto del evento submit
    event.preventDefault();
    
    
    if (comprobarSeleccionTipoAccidente() && checkDatosGenerales() && checkDatosUnidad() && checkDatosTercero() && comprobarOtrosDatos()) {
  // Si todas las funciones han retornado true, entonces ejecuta esta función
  //alert("todo bien");
  this.submit();
}

  
  });
});

</script>

    <script> console.log('Hi!'); </script>
@stop