@extends('adminlte::page')

@section('title', 'Editar Accidente')

@section('content_header')
    <h1>Editar Accidente — Folio {{ $accidente->Folio }} / Tipo {{ $accidente->Tipo }}</h1>
@stop

@section('content')

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
@if (session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form id="form-accidente" method="POST" action="{{ route('registro.actualizar', ['id' => $accidente->Folio, 'tipo' => $accidente->Tipo]) }}">
@csrf

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="{{ route('accidentes.index') }}" class="btn btn-secondary">← Regresar</a>
    </div>
    <div>
        <button type="submit" class="btn btn-success">Guardar cambios</button>
        <a href="{{ route('accidentes.index') }}" class="btn btn-danger ml-2">Cancelar</a>
    </div>
</div>

<ul class="nav nav-tabs" id="accTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#tab-generales" role="tab">Datos generales</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-unidad" role="tab">Datos de la unidad</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-tercero" role="tab">Datos del tercero</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#tab-otros" role="tab">Otros datos</a>
    </li>
</ul>

<div class="tab-content border border-top-0 p-3 bg-white">

    {{-- TAB 1: DATOS GENERALES --}}
    <div class="tab-pane fade show active" id="tab-generales" role="tabpanel">

        <div class="form-row">
            <div class="form-group col-md-2">
                <label>Fecha del accidente</label>
                <input type="date" class="form-control" name="dg_fecha" value="{{ $accidente->Fecha }}">
            </div>
            <div class="form-group col-md-2">
                <label>Hora</label>
                <input type="time" class="form-control" name="dg_hora" value="{{ $accidente->Hora }}">
            </div>
            <div class="form-group col-md-8">
                <label>Lugar del accidente</label>
                <input type="text" class="form-control" name="dg_lugarAccidente" value="{{ $accidente->Lugar }}">
            </div>
        </div>

        <div class="form-group">
            <label>Descripción del accidente</label>
            <textarea class="form-control" name="dg_descripcionAccidente" rows="4">{{ $accidente->Descripcion }}</textarea>
        </div>

        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Causal del accidente</label>
                <select class="form-control" name="ConcepCau">
                    <option value="">— Seleccionar —</option>
                    @foreach ($causales as $c)
                    <option value="{{ $c->IdCau }}" {{ $accidente->IdCau == $c->IdCau ? 'selected' : '' }}>{{ $c->Concepto }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Cargar costos a:</label>
            <div class="btn-group btn-group-toggle d-block" data-toggle="buttons">
                @foreach([1=>'Operador',2=>'Tercero externo',3=>'Costos de operación',4=>'Tercero interno',5=>'RC viajero',6=>'Imagen'] as $v=>$lbl)
                <label class="btn btn-outline-secondary {{ $accidente->CargoCostos == $v ? 'active' : '' }}">
                    <input type="radio" name="dg_radioCargarCostos" value="{{ $v }}" {{ $accidente->CargoCostos == $v ? 'checked' : '' }}> {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-auto">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="dg_responsable" id="dg_responsable" value="s" {{ $accidente->Responsable === 'S' ? 'checked' : '' }}>
                    <label class="form-check-label" for="dg_responsable">El operador es responsable</label>
                </div>
            </div>
            <div class="form-group col-md-auto ml-3">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="dg_presupuestar" id="dg_presupuestar" value="s" {{ $accidente->Presupuestar === 'S' ? 'checked' : '' }}>
                    <label class="form-check-label" for="dg_presupuestar">Presupuestar accidente</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Forma de pago del tercero externo:</label>
            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                <label class="btn btn-outline-secondary {{ $accidente->FormaPagoTer === 'E' ? 'active' : '' }}">
                    <input type="radio" name="FormaPagoTer" value="E" {{ $accidente->FormaPagoTer === 'E' ? 'checked' : '' }}> Efectivo
                </label>
                <label class="btn btn-outline-secondary {{ $accidente->FormaPagoTer === 'V' ? 'active' : '' }}">
                    <input type="radio" name="FormaPagoTer" value="V" {{ $accidente->FormaPagoTer === 'V' ? 'checked' : '' }}> Vales
                </label>
            </div>
        </div>

    </div>

    {{-- TAB 2: DATOS DE LA UNIDAD --}}
    <div class="tab-pane fade" id="tab-unidad" role="tabpanel">

        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Unidad</label>
                <select class="form-control" name="du_unidad" id="du_unidad">
                    <option value="">— Seleccionar —</option>
                    @foreach ($unidades as $u)
                    <option value="{{ $u->IdUnidad }}"
                        data-marca="{{ $u->Marca }}"
                        data-modelo="{{ $u->Modelo }}"
                        data-año="{{ $u->Anio }}"
                        data-color="{{ $u->Color }}"
                        data-placas="{{ $u->Placas }}"
                        {{ $accidente->IdUnidad == $u->IdUnidad ? 'selected' : '' }}>
                        {{ $u->Unidad }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <label>Turno</label>
                <select class="form-control" name="du_turno">
                    <option value="">—</option>
                    <option value="AM" {{ $accidente->Turno === 'AM' ? 'selected' : '' }}>AM</option>
                    <option value="PM" {{ $accidente->Turno === 'PM' ? 'selected' : '' }}>PM</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label>Ruta</label>
                <select class="form-control" name="du_ruta">
                    <option value="">— Seleccionar —</option>
                    @foreach ($rutas as $r)
                    <option value="{{ $r->IdRuta }}" {{ $accidente->IdRuta == $r->IdRuta ? 'selected' : '' }}>{{ $r->Ruta }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-md-5">
                <label>Operador</label>
                <select class="form-control select2-oper" name="du_operador" id="du_oper">
                    <option value="">— Buscar operador —</option>
                    @foreach ($empleados as $e)
                    <option value="{{ $e->IdEmpleado }}" {{ $accidente->IdOper == $e->IdEmpleado ? 'selected' : '' }}>{{ $e->Nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Licencia</label>
                <input type="text" class="form-control" name="du_licencia" value="{{ $accidente->Licencia }}">
            </div>
        </div>

        <div class="form-group">
            <label>Sindicalizado:</label>
            <div class="btn-group btn-group-toggle ml-2" data-toggle="buttons">
                <label class="btn btn-outline-secondary {{ $accidente->Sindicato ? 'active' : '' }}">
                    <input type="radio" name="sindicalizado" value="I" {{ $accidente->Sindicato ? 'checked' : '' }}> Sí
                </label>
                <label class="btn btn-outline-secondary {{ !$accidente->Sindicato ? 'active' : '' }}">
                    <input type="radio" name="sindicalizado" value="E" {{ !$accidente->Sindicato ? 'checked' : '' }}> No
                </label>
            </div>
            <select class="form-control d-inline-block w-auto ml-3" name="Sindicato">
                <option value="">— Sindicato —</option>
                <option value="1" {{ $accidente->Sindicato == 1 ? 'selected' : '' }}>Canek</option>
                <option value="2" {{ $accidente->Sindicato == 2 ? 'selected' : '' }}>Vargas</option>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group col-md-2">
                <label>Marca</label>
                <input type="text" class="form-control" id="du_marca" name="du_marca" value="{{ $accidente->Marca2 }}">
            </div>
            <div class="form-group col-md-3">
                <label>Modelo</label>
                <input type="text" class="form-control" id="du_modelo" name="du_modelo" value="{{ $accidente->Modelo2 }}">
            </div>
            <div class="form-group col-md-2">
                <label>Año</label>
                <input type="text" class="form-control" id="du_año" name="du_año" maxlength="4" value="{{ $accidente->Anio2 }}">
            </div>
            <div class="form-group col-md-2">
                <label>Color</label>
                <input type="text" class="form-control" id="du_color" name="du_color" value="{{ $accidente->Color2 }}">
            </div>
            <div class="form-group col-md-2">
                <label>Placas</label>
                <input type="text" class="form-control" id="du_placas" name="du_placas" value="{{ $accidente->Placas2 }}">
            </div>
        </div>

        <div class="form-group">
            <label>Daños a la unidad</label>
            <textarea class="form-control" name="du_danos" rows="4">{{ $accidente->DaniosUnidad }}</textarea>
        </div>

    </div>

    {{-- TAB 3: DATOS DEL TERCERO --}}
    <div class="tab-pane fade" id="tab-tercero" role="tabpanel">

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="dt_check" id="checkDatosTercero" value="on" {{ $accidente->DatosTercero === 'S' ? 'checked' : '' }}>
            <label class="form-check-label" for="checkDatosTercero"><strong>Involucra vehículo de tercero</strong></label>
        </div>

        <div id="campos-tercero" class="{{ $accidente->DatosTercero === 'S' ? '' : 'd-none' }}">

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Propietario</label>
                    <input type="text" class="form-control" name="dt_propietario" value="{{ $accidente->Propietario }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Teléfono propietario</label>
                    <input type="text" class="form-control" name="dt_telefonoPropietario" value="{{ $accidente->TelProp }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label>Conductor</label>
                    <input type="text" class="form-control" name="dt_conductor" value="{{ $accidente->Conductor }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Teléfono conductor</label>
                    <input type="text" class="form-control" name="dt_telefonoConductor" value="{{ $accidente->TelConduc }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-2">
                    <label>Marca</label>
                    <input type="text" class="form-control" name="dt_marca" value="{{ $accidente->Marca1 }}">
                </div>
                <div class="form-group col-md-3">
                    <label>Modelo</label>
                    <input type="text" class="form-control" name="dt_modelo" value="{{ $accidente->Modelo1 }}">
                </div>
                <div class="form-group col-md-2">
                    <label>Año</label>
                    <input type="text" class="form-control" name="dt_año" maxlength="4" value="{{ $accidente->Anio1 }}">
                </div>
                <div class="form-group col-md-2">
                    <label>Color</label>
                    <input type="text" class="form-control" name="dt_color" value="{{ $accidente->Color1 }}">
                </div>
                <div class="form-group col-md-2">
                    <label>Placas</label>
                    <input type="text" class="form-control" name="dt_placas" value="{{ $accidente->Placas1 }}">
                </div>
            </div>

            <div class="form-group">
                <label>Daños al tercero</label>
                <textarea class="form-control" name="dt_daños" rows="4">{{ $accidente->DaniosTercero }}</textarea>
            </div>

            <div class="form-group">
                <label>Costo aproximado (tercero)</label>
                <input type="number" step="0.01" class="form-control" style="max-width:200px" name="CostoAproxTer" value="{{ $accidente->CostoAproxTer }}">
            </div>

        </div>
    </div>

    {{-- TAB 4: OTROS DATOS --}}
    <div class="tab-pane fade" id="tab-otros" role="tabpanel">

        <div class="row">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ot_lesionadosCheck" id="ot_lesionadosCheck" value="on" {{ $accidente->Lesionados === 'S' ? 'checked' : '' }}>
                    <label class="form-check-label" for="ot_lesionadosCheck">Lesionados</label>
                </div>
                <textarea class="form-control mt-1" name="ot_lesionadosExtra" id="ot_lesionadosExtra" rows="4" {{ $accidente->Lesionados !== 'S' ? 'disabled' : '' }}>{{ $accidente->NomLesionados }}</textarea>
            </div>

            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="ot_datosAseguradoraCheck" id="ot_datosAseguradora" value="on" {{ $accidente->Aseguradora === 'S' ? 'checked' : '' }}>
                    <label class="form-check-label" for="ot_datosAseguradora">Datos de la aseguradora</label>
                </div>
                <div class="form-row mt-1">
                    <div class="form-group col-md-7">
                        <label>Ajustador</label>
                        <input type="text" class="form-control" name="ot_ajustador" id="ot_ajustador" value="{{ $accidente->Ajustador }}" {{ $accidente->Aseguradora !== 'S' ? 'disabled' : '' }}>
                    </div>
                    <div class="form-group col-md-5">
                        <label>Número CQ</label>
                        <input type="text" class="form-control" name="ot_numeroCq" id="ot_numeroCq" value="{{ $accidente->NumCQ }}" {{ $accidente->Aseguradora !== 'S' ? 'disabled' : '' }}>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Costo aproximado</label>
                        <input type="number" step="0.01" class="form-control" name="ot_costoAproximado" id="ot_costoAproximado" value="{{ $accidente->CostoAprox }}" {{ $accidente->Aseguradora !== 'S' ? 'disabled' : '' }}>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Costo real</label>
                        <input type="number" step="0.01" class="form-control" name="ot_costoReal" value="{{ $accidente->CostoReal }}">
                    </div>
                </div>

                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="ot_sspCheck" id="ot_ssp" value="on" {{ $accidente->SSP === 'S' ? 'checked' : '' }}>
                    <label class="form-check-label" for="ot_ssp">S.S.P.</label>
                </div>
                <div class="form-group mt-1">
                    <label>Perito</label>
                    <input type="text" class="form-control" name="ot_perito" id="ot_perito" value="{{ $accidente->Perito }}" {{ $accidente->SSP !== 'S' ? 'disabled' : '' }}>
                </div>
            </div>
        </div>

        <div class="form-row mt-3">
            <div class="form-group col-md-6">
                <label>Gerente</label>
                <input type="text" class="form-control" name="ot_gerente" value="{{ $accidente->Gerente }}">
            </div>
        </div>

        <div class="form-group">
            <label>Observaciones</label>
            <textarea class="form-control" name="ot_observaciones" rows="3">{{ $accidente->Observ }}</textarea>
        </div>

        <div class="form-group">
            <label>Comentarios</label>
            <textarea class="form-control" name="ot_comentarios" rows="2">{{ $accidente->Comentarios }}</textarea>
        </div>

    </div>

</div>{{-- /.tab-content --}}

<div class="mt-3">
    <button type="submit" class="btn btn-success">Guardar cambios</button>
    <a href="{{ route('accidentes.index') }}" class="btn btn-secondary ml-2">Cancelar</a>
</div>

</form>
@stop

@section('css')
<link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
<script>
$(document).ready(function(){
    $('#du_oper').select2({width:'100%'});
});

// Unidad → autocompletar datos del vehículo
document.getElementById('du_unidad').addEventListener('change', function(){
    var o = this.options[this.selectedIndex];
    ['marca','modelo','año','color','placas'].forEach(function(f){
        var el = document.getElementById('du_'+f);
        if(el) el.value = o.getAttribute('data-'+f) || '';
    });
});

// Tercero toggle
document.getElementById('checkDatosTercero').addEventListener('change', function(){
    document.getElementById('campos-tercero').classList.toggle('d-none', !this.checked);
});

// Lesionados toggle
document.getElementById('ot_lesionadosCheck').addEventListener('change', function(){
    document.getElementById('ot_lesionadosExtra').disabled = !this.checked;
});

// Aseguradora toggle
document.getElementById('ot_datosAseguradora').addEventListener('change', function(){
    ['ot_ajustador','ot_costoAproximado','ot_numeroCq'].forEach(function(id){
        document.getElementById(id).disabled = !document.getElementById('ot_datosAseguradora').checked;
    });
});

// SSP toggle
document.getElementById('ot_ssp').addEventListener('change', function(){
    document.getElementById('ot_perito').disabled = !this.checked;
});
</script>
@stop
