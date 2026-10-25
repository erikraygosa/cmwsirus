@extends('combustible.layout')
@section('fuel_content')
<div class="card card-body"><h2 class="h4">Confirmar precio</h2>
<p>Fecha: <strong>{{ $input['Fecha'] }}</strong> · Precio propuesto: <strong>${{ $input['Costo'] }}</strong></p>
@if($state['price'])<p>Precio actual: ${{ $state['price']->Costo }}</p>@else<p>Se agregará un precio nuevo.</p>@endif
<p>Hay <strong>{{ count($state['rows']) }} cargas activas</strong> asociadas a este precio. Las canceladas se conservan sin cambios.</p>
<form method="post" action="{{ route('combustible.prices.save') }}" data-fuel-mutation data-redirect="{{ route('combustible.prices') }}">
@csrf<input type="hidden" name="Confirmacion" value="{{ $confirmation }}"><input type="hidden" name="Operacion" value="{{ $token }}">
<fieldset><legend class="h6">¿Recalcular también las cargas activas indicadas?</legend>
<label class="d-block"><input type="radio" name="Recalcular" value="no" required> No, conservar sus precios e importes históricos</label>
<label class="d-block"><input type="radio" name="Recalcular" value="si" required> Sí, actualizar sus precios e importes y auditar cada carga</label></fieldset>
<button type="submit" class="btn btn-primary">Confirmar precio y opción seleccionada</button><a class="btn btn-secondary" href="{{ route('combustible.prices') }}">Volver</a>
</form>
<details class="mt-3"><summary>Ver cargas que se recalcularían</summary><ul>@foreach($state['rows'] as $row)<li>#{{ $row['IdCarga'] }} · {{ $row['Unidad'] }} · {{ $row['Litros'] }} litros · ${{ $row['Importe'] }}</li>@endforeach</ul></details>
</div>
@stop
