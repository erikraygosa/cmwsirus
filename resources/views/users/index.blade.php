@extends('adminlte::page')

@section('title', 'Usuarios')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-users text-primary mr-2"></i>Usuarios</h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus mr-1"></i> Nuevo usuario
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

    <div class="card card-outline card-primary">
        <div class="card-header">
            <form method="GET" action="{{ route('users.index') }}" class="d-flex" style="gap:.5rem;">
                <input type="text" name="q" value="{{ $busqueda ?? '' }}"
                       class="form-control form-control-sm" placeholder="Buscar por nombre o email…"
                       autocomplete="off" style="max-width:300px;">
                <button class="btn btn-sm btn-primary" type="submit"><i class="fas fa-search"></i></button>
                @if($busqueda ?? false)
                    <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th class="text-center" width="120">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td class="text-muted" style="font-size:.82rem;">{{ $user->id }}</td>
                            <td>
                                <i class="fas fa-user-circle text-secondary mr-1"></i>
                                <strong>{{ $user->name }}</strong>
                                @if($user->id === auth()->id())
                                    <span class="badge badge-info ml-1" style="font-size:.65rem;">tú</span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:.85rem;">{{ $user->email }}</td>
                            <td>
                                @forelse($user->roles as $role)
                                    <span class="badge badge-{{ $role->name === 'Admin' ? 'danger' : ($role->name === 'Supervisor' ? 'warning' : 'secondary') }}">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-muted" style="font-size:.78rem;">Sin rol</span>
                                @endforelse
                            </td>
                            <td class="text-center">
                                <a href="{{ route('users.edit', $user->id) }}"
                                   class="btn btn-xs btn-outline-primary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $user->id) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Eliminar usuario {{ addslashes($user->name) }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-search mr-1"></i>
                                No se encontraron usuarios{{ $busqueda ? " para \"$busqueda\"" : '' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </div>

@stop

@section('css')
<style>
    .btn-xs { padding: .15rem .4rem; font-size: .75rem; }
    .table td, .table th { vertical-align: middle !important; }
</style>
@stop
