@extends('combustible.layout')
@section('fuel_content')
<div class="card card-body"><h2 class="h4">Auditoría de combustible</h2>
<p>Consulte el usuario, la operación y los valores anteriores y nuevos. Una operación pendiente requiere revisión o reintento con su identificador; no implica que todos sus cambios se hayan aplicado.</p>
<form method="get"><label>Identificador de operación<input class="form-control" name="operacion" value="{{ request('operacion') }}" maxlength="36"></label><button class="btn btn-primary">Buscar</button></form></div>
@forelse($rows as $row)
<article class="card"><div class="card-header"><strong>{{ $row->action }}</strong> · {{ $row->Fecha }} (Mérida)
<span class="badge {{ $row->complete?'badge-success':'badge-warning' }}">{{ $row->complete?'COMPLETA':'PENDIENTE' }}</span></div>
<div class="card-body"><p>{{ $row->actor['name'] }} · Usuario #{{ $row->actor['id'] }}<br>Identificador: <code>{{ $row->id }}</code></p>
@if($row->diagnostic)<p class="alert alert-warning">{{ $row->diagnostic }}</p>@endif
@foreach($row->entries as $entry)
<details class="mb-2"><summary>{{ $entry['action'] }} · {{ $entry['table'] }} · {{ json_encode($entry['key'],JSON_UNESCAPED_UNICODE) }}</summary>
<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Campo</th><th>Anterior</th><th>Nuevo</th></tr></thead><tbody>
@foreach(array_unique(array_merge(array_keys($entry['before']??[]),array_keys($entry['after']??[]))) as $field)
@php($before=$entry['before'][$field]??null)
@php($after=$entry['after'][$field]??null)
@if($before!==$after)
<tr><th>{{ $field }}</th><td>{{ $before===null?'—':$before }}</td><td>{{ $after===null?'—':$after }}</td></tr>
@endif
@endforeach
</tbody></table></div></details>
@endforeach
</div></article>
@empty<p>No hay operaciones con estos filtros.</p>@endforelse
{{ $rows->links('pagination::bootstrap-4') }}
@stop
