@extends('adminlte::page')

@section('title', 'Agregar Imagenes')

@section('content_header')
    <h1>Agregar Imagenes</h1>
@stop

@section('content')
@if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Mensaje de éxito -->
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
    <div class="container">
        <div class="card">
        <div class="card-body bg-white">
                        <form method="POST" action="{{ route('accidente.imagenStore') }}"  role="form" enctype="multipart/form-data">
                            @csrf
                            <div class="row padding-1 p-1">
                                <div class="col-md-12">
                                <div class="form-group mb-2 mb20">
                                    <!-- <label for="imagen" class="form-label">Subir Archivos del Accidente</label> -->
                                    <div class="mb-3">
                                    <label for="imagen" class="form-label">Subir Archivos</label>
                                    <input class="form-control" type="file" accept="image/*" name="imagen" id="imagen" multiple>
                                    </div>

                                     <input type="hidden" name="accidente_id" value="{{$id}}">
                            
                                <div class="col-md-12 mt20 mt-2">
                                 <button type="submit" class="btn btn-primary">Agregar</button>
                                 </div>
                             </div>
                            </div>
                        </form>
                    </div>
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