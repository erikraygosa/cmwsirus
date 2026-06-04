@extends('adminlte::page')

@section('title', 'Editar Rol')

@section('content_header')
    <div class="d-flex align-items-center">
        <a href="{{ route('roles.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1 class="m-0"><i class="fas fa-shield-alt text-warning mr-2"></i>Editar Rol</h1>
    </div>
@stop

@section('content')

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h6 class="m-0">Rol: <strong>{{ $role->name }}</strong></h6>
                </div>
                <div class="card-body">

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                        @csrf @method('PUT')

                        <div class="form-group">
                            <label class="font-weight-bold">Nombre del rol</label>
                            <input type="text" name="name"
                                   value="{{ old('name', $role->name) }}"
                                   class="form-control @error('name') is-invalid @enderror" required>
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
                                    @php $checked = $role->permissions->contains('name', $perm->name); @endphp
                                    <div class="col-12 col-sm-6 mb-1">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox"
                                                   class="custom-control-input chk-perm"
                                                   name="permissions[]"
                                                   value="{{ $perm->name }}"
                                                   id="perm_{{ $perm->id }}"
                                                   {{ $checked ? 'checked' : '' }}>
                                            <label class="custom-control-label font-weight-{{ $checked ? 'bold' : 'normal' }}"
                                                   for="perm_{{ $perm->id }}"
                                                   style="font-size:.85rem;">
                                                <code>{{ $perm->name }}</code>
                                                @if($perm->description ?? false)
                                                    <span class="text-muted"> — {{ $perm->description }}</span>
                                                @endif
                                                @if($checked)
                                                    <i class="fas fa-check text-success ml-1" style="font-size:.7rem;"></i>
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
                                <i class="fas fa-save mr-1"></i> Guardar cambios
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
