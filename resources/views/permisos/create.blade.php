@extends('adminlte::page')

@section('title', 'Nuevo Permiso')

@section('content_header')
    <div class="d-flex align-items-center">
        <a href="{{ route('permisos.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="m-0"><i class="fas fa-key text-info mr-2"></i>Nuevo Permiso</h1>
    </div>
@stop

@section('content')

    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="card card-outline card-info">
                <div class="card-body">

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('permisos.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label class="font-weight-bold">Nombre del permiso</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="solo_minusculas_sin_espacios"
                                   pattern="[a-z0-9_]+"
                                   title="Solo minúsculas, números y guión bajo"
                                   required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @else
                                <small class="text-muted">Solo letras minúsculas, números y guión bajo. Ej: <code>expedientes</code></small>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">
                                Descripción
                                <small class="text-muted font-weight-normal">(opcional)</small>
                            </label>
                            <input type="text" name="description" value="{{ old('description') }}"
                                   class="form-control" placeholder="Descripción legible del permiso"
                                   maxlength="200">
                        </div>

                        <div class="alert alert-warning py-2" style="font-size:.82rem;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Después de crear el permiso, asígnalo a los roles que lo necesiten
                            desde <a href="{{ route('roles.index') }}">Roles</a>.
                        </div>

                        <div class="d-flex justify-content-end" style="gap:.5rem;">
                            <a href="{{ route('permisos.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-save mr-1"></i> Crear permiso
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

@stop
