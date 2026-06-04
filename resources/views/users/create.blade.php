@extends('adminlte::page')

@section('title', 'Nuevo Usuario')

@section('content_header')
    <div class="d-flex align-items-center">
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="m-0"><i class="fas fa-user-plus text-primary mr-2"></i>Nuevo Usuario</h1>
    </div>
@stop

@section('content')

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card card-outline card-primary">
                <div class="card-body">

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label class="font-weight-bold">Nombre</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Nombre completo" required autofocus>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="form-control @error('email') is-invalid @enderror"
                                   placeholder="correo@ejemplo.com" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Contraseña</label>
                            <input type="password" name="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Mínimo 8 caracteres" required>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation"
                                   class="form-control" placeholder="Repite la contraseña" required>
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Roles</label>
                            <div class="row">
                                @foreach($roles as $role)
                                    <div class="col-12 col-sm-6 col-md-4 mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   class="custom-control-input"
                                                   name="roles[]"
                                                   value="{{ $role->id }}"
                                                   id="role_{{ $role->id }}"
                                                   {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="role_{{ $role->id }}">
                                                {{ $role->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-end" style="gap:.5rem;">
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Crear usuario
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

@stop
