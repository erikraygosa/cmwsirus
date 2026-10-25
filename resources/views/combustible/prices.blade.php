@extends('combustible.layout')
@section('fuel_content')
<div class="card card-body"><h2 class="h4">Precio de diésel</h2>
<p>Seleccione una fecha para agregar o corregir su precio. Las cargas existentes sólo se recalculan con confirmación explícita.</p>
<form method="get" action="{{ route('combustible.prices.review') }}" class="row align-items-end">
<div class="col-md-4"><label for="Fecha">Fecha</label><input id="Fecha" name="Fecha" type="date" class="form-control" value="{{ request('Fecha',$today) }}" required></div>
<div class="col-md-4"><label for="Costo">Precio por litro</label><input id="Costo" name="Costo" type="number" step="0.001" min="0.001" max="999999.999" class="form-control" value="{{ request('Costo') }}" required></div>
<div class="col-md-4"><button type="submit" class="btn btn-primary">Revisar y confirmar</button></div>
</form></div>
<div class="card table-responsive"><table class="table"><thead><tr><th>Fecha</th><th>Precio por litro</th><th>Acciones</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>{{ $row->Fecha }}</td><td>${{ $row->Costo }}</td><td><a href="{{ route('combustible.prices',['Fecha'=>$row->Fecha,'Costo'=>$row->Costo]) }}">Corregir</a>
@if($rows->currentPage()===1 && $loop->first)
<form method="post" action="{{ route('combustible.prices.delete',$row->Fecha) }}" data-fuel-mutation data-confirm="¿Eliminar este último precio? El servidor comprobará que no tenga cargas asociadas, incluidas canceladas.">
@csrf @method('DELETE')<input type="hidden" name="Operacion" value="{{ (string) Illuminate\Support\Str::uuid() }}"><input type="hidden" name="Version" value="{{ app(App\Services\Combustible\FuelService::class)->version($row) }}"><button type="submit" class="btn btn-sm btn-outline-danger">Eliminar último precio</button></form>
@endif</td></tr>@empty<tr><td colspan="3">No hay precios registrados.</td></tr>@endforelse
</tbody></table></div>
{{ $rows->links('pagination::bootstrap-4') }}
@stop
