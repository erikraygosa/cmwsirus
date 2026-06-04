@extends('adminlte::page')

@section('title', 'Roles')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-shield-alt text-warning mr-2"></i>Roles</h1>
        <a href="{{ route('roles.create') }}" class="btn btn-warning btn-sm">
            <i class="fas fa-plus mr-1"></i> Nuevo rol
        </a>
    </div>
@stop

@section('content')

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card card-outline card-warning">
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Rol</th>
                        <th>Permisos</th>
                        <th class="text-center" width="80">Usuarios</th>
                        <th class="text-center" width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                        <tr>
                            <td class="text-muted" style="font-size:.82rem;">{{ $role->id }}</td>
                            <td>
                                <span class="badge badge-{{ $role->name === 'Admin' ? 'danger' : ($role->name === 'Supervisor' ? 'warning' : 'secondary') }} mr-1">
                                    {{ $role->name }}
                                </span>
                            </td>
                            <td>
                                @forelse($role->permissions as $perm)
                                    <span class="badge badge-light border" style="font-size:.72rem;">{{ $perm->name }}</span>
                                @empty
                                    <span class="text-muted" style="font-size:.78rem;">Sin permisos</span>
                                @endforelse
                            </td>
                            <td class="text-center">
                                <span class="badge badge-info">{{ $role->users_count }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('roles.edit', $role->id) }}"
                                   class="btn btn-xs btn-outline-warning" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('roles.destroy', $role->id) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar rol {{ addslashes($role->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger"
                                            title="Eliminar"
                                            {{ $role->users_count > 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay roles registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@stop

@section('css')
<style>
    .btn-xs { padding: .15rem .4rem; font-size: .75rem; }
    .table td, .table th { vertical-align: middle !important; }
</style>
@stop
