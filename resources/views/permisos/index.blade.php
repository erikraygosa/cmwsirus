@extends('adminlte::page')

@section('title', 'Permisos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-key text-info mr-2"></i>Permisos</h1>
        <a href="{{ route('permisos.create') }}" class="btn btn-info btn-sm">
            <i class="fas fa-plus mr-1"></i> Nuevo permiso
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

    <div class="card card-outline card-info">
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Permiso</th>
                        <th>Descripción</th>
                        <th>Roles que lo tienen</th>
                        <th class="text-center" width="80">Eliminar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($permissions as $perm)
                        <tr>
                            <td class="text-muted" style="font-size:.82rem;">{{ $perm->id }}</td>
                            <td><code>{{ $perm->name }}</code></td>
                            <td class="text-muted" style="font-size:.85rem;">
                                {{ $perm->description ?? '—' }}
                            </td>
                            <td>
                                @forelse($perm->roles as $role)
                                    <span class="badge badge-{{ $role->name === 'Admin' ? 'danger' : ($role->name === 'Supervisor' ? 'warning' : 'secondary') }}">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-muted" style="font-size:.78rem;">Ninguno</span>
                                @endforelse
                            </td>
                            <td class="text-center">
                                <form action="{{ route('permisos.destroy', $perm->id) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar permiso {{ addslashes($perm->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="btn btn-xs btn-outline-danger"
                                            title="Eliminar"
                                            {{ $perm->roles->count() > 0 ? 'disabled' : '' }}>
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No hay permisos registrados.</td>
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
