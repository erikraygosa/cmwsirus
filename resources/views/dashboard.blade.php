@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Dashboard {{ $fechadia}} </h1>
@stop

@section('content')
<div>
<div class="card text-white bg-primary mb-3" style="max-width: 18rem;">
  <div class="card-header">Resumen de Plan de Trabajo</div>
  <div class="card-body">
    <h5 class="card-title">Total de Rutas = {{ $rutas}}</h5>
    <p class="card-text">Total de Unidades = {{ $unidadesProgramadas}}</p>
  </div>
</div>

  <div>
  <div class="card text-white bg-danger mb-3" style="max-width: 18rem;">
  <div class="card-header">Resumen de Accidentes</div>
  <div class="card-body">
    <h5 class="card-title">Total de Accidentes Afectados = {{ $afectados }} </h5>
    <p class="card-text">Total de Accidentes Responsables = {{ $responsable }}</p>
    <p class="card-text">Total de Accidentes  = {{ $totalaccidentes }}</p>
  </div>
  </div>
  
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop