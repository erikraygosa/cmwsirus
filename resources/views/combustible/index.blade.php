@extends('combustible.layout')
@section('fuel_content')
<details class="mb-3"><summary>Recuperar un envío pendiente</summary>
<p>Use el identificador del envío original. Se exige el mismo usuario y permiso de la acción. Si hubo cambios externos, será necesaria una conciliación; no se sobrescribirán.</p>
<form method="post" action="{{ route('combustible.resume') }}" data-fuel-mutation>@csrf<label>Identificador de operación<input class="form-control" name="Operacion" required maxlength="36"></label> <button type="submit" class="btn btn-outline-primary">Reintentar operación original</button></form></details>
<form class="card card-body" method="get">
<div class="row">
<div class="col-md-2"><label for="desde">Desde</label><input id="desde" name="desde" type="date" class="form-control" value="{{ $filters['desde'] }}" required></div>
<div class="col-md-2"><label for="hasta">Hasta</label><input id="hasta" name="hasta" type="date" class="form-control" value="{{ $filters['hasta'] }}" required></div>
<div class="col-md-2"><label for="tipo_fecha">Filtrar por</label><select id="tipo_fecha" name="tipo_fecha" class="form-control"><option value="carga" @selected($filters['tipo_fecha']==='carga')>Fecha de carga</option><option value="trabajo" @selected($filters['tipo_fecha']==='trabajo')>Fecha de trabajo</option></select></div>
<div class="col-md-2"><label for="unidad">Unidad</label><select id="unidad" name="IdUnidad" class="form-control"><option value="">Todas</option>@foreach($units as $unit)<option value="{{ $unit->IdUnidad }}" @selected(($filters['IdUnidad']??'')==$unit->IdUnidad)>{{ $unit->Unidad }}{{ $unit->Estatus!=='A'?' (inactiva)':'' }}</option>@endforeach</select></div>
<div class="col-md-2"><label for="turno">Turno</label><select id="turno" name="Turno" class="form-control"><option value="">Todos</option>@foreach(['AM','PM'] as $t)<option @selected(($filters['Turno']??'')===$t)>{{ $t }}</option>@endforeach</select></div>
<div class="col-md-2"><label for="estado">Estatus</label><select id="estado" name="Estatus" class="form-control"><option value="">Todos</option><option value="A" @selected(($filters['Estatus']??'')==='A')>Activas</option><option value="C" @selected(($filters['Estatus']??'')==='C')>Canceladas</option></select></div>
</div>
<div class="mt-3"><button class="btn btn-primary">Consultar</button>
@can('combustible_exportar')<a class="btn btn-outline-success" href="{{ route('combustible.export',['format'=>'xlsx',...$filters]) }}">Excel</a> <a class="btn btn-outline-danger" href="{{ route('combustible.export',['format'=>'pdf',...$filters]) }}">PDF</a>@endcan
</div>
</form>
<div class="alert alert-info">Totales de todas las cargas activas filtradas: <strong>{{ $totals['Litros'] }} litros · ${{ $totals['Importe'] }}</strong>. Las canceladas no se suman.</div>
<div class="card table-responsive"><table class="table table-striped"><thead><tr><th>Folio</th><th>Ticket</th><th>Carga</th><th>Trabajo</th><th>Turno</th><th>Unidad</th><th>Km</th><th>Litros</th><th>Precio (fecha)</th><th>Importe</th><th>Estatus</th><th>Acciones</th></tr></thead><tbody>
@forelse($rows as $row)
<tr class="{{ $row->Estatus==='C'?'table-secondary':'' }}"><td>{{ $row->IdCarga }}</td><td>{{ $row->NumeroTicket??'—' }}</td><td>{{ $row->FechaCarga }}</td><td>{{ $row->FechaTrabajo }}</td><td>{{ $row->Turno }}</td><td>{{ $row->Unidad }}</td><td>{{ $row->Km??'—' }}</td><td>{{ $row->Litros }}</td><td>{{ $row->PrecioLitro }} ({{ $row->FechaPrecio }})</td><td>{{ $row->Importe }}</td><td><strong>{{ $row->Estatus==='A'?'ACTIVA':'CANCELADA' }}</strong>@if($row->Estatus==='C')<p>{{ $row->MotivoCancelacion }}</p>@endif</td>
<td>
@if($row->Estatus==='A')
@can('combustible_editar')<a href="{{ route('combustible.edit',$row->IdCarga) }}">Editar</a>@endcan
@can('combustible_cancelar')
<details><summary>Cancelar</summary><form method="post" action="{{ route('combustible.cancel',$row->IdCarga) }}" data-fuel-mutation data-confirm="¿Cancelar esta carga? No se eliminará su historial.">
@csrf<input type="hidden" name="Operacion" value="{{ (string) Illuminate\Support\Str::uuid() }}"><input type="hidden" name="Version" value="{{ app(App\Services\Combustible\FuelService::class)->version($row) }}">
<label>Motivo obligatorio<input name="MotivoCancelacion" class="form-control" required maxlength="255"></label><button type="submit" class="btn btn-sm btn-danger">Confirmar cancelación</button></form></details>
@endcan
@endif
<details><summary>Detalle</summary><p>{{ $row->Observaciones }}</p><small>Registro: {{ $row->UsuarioRegistro }} · {{ $row->FechaRegistro }}<br>Token: {{ $row->TokenRegistro }}</small></details>
</td></tr>
@empty<tr><td colspan="12">No hay cargas con estos filtros.</td></tr>@endforelse
</tbody></table></div>
<div>{{ $rows->links('pagination::bootstrap-4') }}</div>
@stop
