@extends('combustible.layout')
@section('fuel_content')
<div class="alert alert-warning" role="alert">{{ $message }}</div>
<p>No se pudo cargar esta pantalla. Contacte al administrador para revisar los bloqueos de MySQL.</p>
@stop
