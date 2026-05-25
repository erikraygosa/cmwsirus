@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <h1>Lista de Usuarios </h1>
@stop

@section('content')
@livewire('adminuser', [
   
        ])  
@endsection
 @section('footer')
   <div class="float-left d-none d-sm-block">
        <b>Versión</b> 1.0
    </div>
    <strong>© 2024 <a href="https://www.facebook.com/erik.raygosachac?mibextid=ZbWKwL">Control Bus</a> by Erik Raygosa.</strong> Todos los derechos reservados.
@stop
@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop