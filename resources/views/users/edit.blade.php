@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Asignar un Rol </h1>
@stop

@section('content')

    @if (session('info'))

        <div class="alert alert-success">

            <strong>{{session('info')}}</strong>
        </div>

    @endif

    <div class="card">
        <div class="card">
            <div class="card-body">
                <p class="h5">Nombre</p>
                <p class="form-control">{{$user->name}}</p>
                <p class="h5">Listado de Roles</p>

                <form action="{{ route('users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <!-- Aquí van tus campos del formulario -->
                    @foreach ($roles as $role)
                    <div>
                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" class="mr-1">
                     {{ $role->name }}
                    </div>
                    @endforeach
                  

                    <div>
                        <button type="submit">Asignar Rol</button>
                    </div>
                </form>



        </div>

    </div>

@endsection


@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script> console.log('Hi!'); </script>
@stop