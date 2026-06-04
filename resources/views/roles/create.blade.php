@extends('adminlte::page')

@section('title', 'Nuevo Rol')

@section('content_header')
    <div class="d-flex align-items-center">
        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="m-0"><i class="fas fa-shield-alt text-warning mr-2"></i>Nuevo Rol</h1>
    </div>
@stop

@section('content')

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card card-outline card-warning">
                <div class="card-body">

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label class="font-weight-bold">Nombre del rol</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="Ej: Supervisor" required autofocus>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group mb-4">
                            <label class="font-weight-bold">Permisos</label>
                            <div class="mb-1">
                                <a href="#" id="selAll" class="text-primary mr-2" style="font-size:.8rem;">Seleccionar todos</a>
                                <a href="#" id="deselAll" class="text-secondary" style="font-size:.8rem;">Deseleccionar</a>
                            </div>
                            <div class="row">
                                @foreach($permissions as $perm)
                                    <div class="col-12 col-sm-6 mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   class="custom-control-input chk-perm"
                                                   name="permissions[]"
                                                   value="{{ $perm->name }}"
                                                   id="perm_{{ $perm->id }}"
                                                   {{ in_array($perm->name, old('permissions', [])) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="perm_{{ $perm->id }}"
                                                   style="font-size:.85rem;">
                                                <code>{{ $perm->name }}</code>
                                                @if($perm->description ?? false)
                                                    <span class="text-muted"> — {{ $perm->description }}</span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-flex justify-content-end" style="gap:.5rem;">
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i> Crear rol
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

@stop

@section('js')
<script>
document.getElementById('selAll')?.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.chk-perm').forEach(c => c.checked = true);
});
document.getElementById('deselAll')?.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.chk-perm').forEach(c => c.checked = false);
});
</script>
@stop
