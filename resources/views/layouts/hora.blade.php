<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Horarios de Trabajo</title>
        <!-- Favicon-->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico')}}" />
        <!-- Core theme CSS (includes Bootstrap)-->
        <link href="{{ asset('styles.css')}}" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/admin_custom.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.0/css/responsive.bootstrap4.css" />
    @yield('css')
    </head>
    <body>
        <!-- Responsive navbar-->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark" style="background-color: #142029;">
            <div class="container">
            
            <!-- <img src="{{ asset('storage/carrusel/favicon.jpg')}}" class="d-inline-block align-text-top" alt="" width="30" height="24"> -->
                <a class="navbar-brand" href="horariodia">Horario</a>
                <a class="navbar-brand" href="horariodiasig">Horario dia siguiente</a>
             
               
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <!-- <li class="nav-item"><a class="nav-link active" aria-current="page" href="#">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="#">Link</a></li> -->
                        <li class="nav-item dropdown">
                        @auth
                            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}</a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <form method="POST" action="{{ route('logout') }}" x-data>
                               @csrf
                                <!-- <li><a class="dropdown-item" href="{{ route('logout') }}"
                                        @click.prevent="$root.submit();">Salir</a></li> -->
                            </form>


                                <!-- <li><a class="dropdown-item" href="{{ route('profile.show') }}">Mi perfil</a></li> -->
                                <li><a class="dropdown-item"  href="{{ url('/dashboard') }}" >Dashboard</a></li>
                               
                            </ul>
                        
                        @else
                        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                           Iniciar Sesion</a>
                            
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="{{ route('login') }}">Login</a></li>
                               
                              
                            </ul>
                        @endauth   
                        </li>
                    </ul>
                </div>
            </div >
        </nav>
        <!-- Page content-->

        <main >
        <div class="container-fluid">   
        @yield('content')
        </div> 
        </main>
     
    </body>
    <footer>
        @yield('footer')
    </footer>
    @yield('js')
        <!-- Bootstrap core JS-->
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
        <!-- Core theme JS-->
        <script src="{{ asset('scripts.js') }}"></script>
        <script src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/responsive.bootstrap4.js"></script>
</html>
  