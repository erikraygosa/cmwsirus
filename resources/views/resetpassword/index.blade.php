@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <!-- <h1>Horarios</h1> -->

    
@stop

@section('content')


<div class="container-fluid">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            @if (session('status'))
                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card">
                <div class="card-header">Cambiar Contraseña</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('reset.store') }}">
                        @csrf

                       

                        <div class="form-group">
                            <label for="new_password">Nueva Contraseña</label>
                            <input id="new_password" type="password" class="form-control" name="new_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password_confirmation">Confirmar Nueva Contraseña</label>
                            <input id="new_password_confirmation" type="password" class="form-control" name="new_password_confirmation" required>
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">
                                Cambiar Contraseña
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


 
</div>
@endsection

@section('footer')
   <!-- <div class="float-left d-none d-sm-block">
        <b>Versión</b> 1.0
    </div>
    <strong>© 2024 <a href="https://www.facebook.com/erik.raygosachac?mibextid=ZbWKwL">Control Mant Web</a> </strong> Todos los derechos reservados. -->
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.0/css/responsive.bootstrap4.css" />
    
@stop

@section('js')
<script src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/responsive.bootstrap4.js"></script>
<script>
$('#planservice').dataTable({
    responsive: true,
    autoWidth: false
});
</script>
@stop


