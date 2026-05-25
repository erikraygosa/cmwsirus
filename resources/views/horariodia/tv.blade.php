@extends('layouts.hora')

@section('content')
<body class="dynamic-background" style="background-image: url('{{ asset('images/back.png') }}'); margin: 0; overflow: hidden;">
<div class="text-center" style="padding: 10px;">
    @php
        $message = $message->mensaje ?? '🚌 ¡Bienvenidos! Consulte las rutas y horarios programados del día.';
    @endphp
    <marquee behavior="scroll" direction="left" style="color: #ffeb3b; font-size: 36px; margin-bottom: 20px;">
        {{ $message }}
    </marquee>

    <h1 style="color: white; font-size: 64px; margin: 0;">{{ $tituloHorario }}</h1>
    <h2 style="color: white; font-size: 48px;">Fecha de Trabajo: {{ $fechaProgramada }}</h2>
</div>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card bg-dark border-light" style="margin: 0 20px;">
                <div class="card-body p-2">
                    <div class="table-responsive">
                        <table class="table table-dark table-bordered table-striped text-center" id="hora">
                            <thead style="font-size: 28px;">
                                <tr>
                                    <th>RUTA</th>
                                    <th>OPERADOR</th>
                                    <th>UNIDAD</th>
                                    <th>CORRIDA</th>
                                    <th>TURNO</th>
                                    <th>H. INICIO</th>
                                    <th>H. FIN</th>
                                </tr>
                            </thead>
                            <tbody id="paginatedRows" style="font-size: 26px;">
                                {{-- Generado por JS --}}
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-2">
                        <span id="page-indicator" style="color: white; font-size: 24px;"></span><br>
                        <span id="loading-indicator" style="display: none; color: #00e5ff; font-size: 20px;">⏳ Cambiando página...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    .table-flash {
        animation: blink 1s ease-in-out 3;
    }

    .dynamic-background {
        background-size: cover;
        background-repeat: no-repeat;
        background-attachment: fixed;
        height: 100vh;
        width: 100%;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: black;
    }
</style>
@endsection

@section('js')
<script>
    const allRows = @json($pservicios);
    const rowsPerPage = {{ $rowsPerPage }};
    const intervaloRotacion = {{ $intervaloMs }};

    let currentPage = 0;

    function renderPage() {
        const start = currentPage * rowsPerPage;
        const end = start + rowsPerPage;
        const rows = allRows.slice(start, end);

        const tbody = document.getElementById('paginatedRows');
        tbody.innerHTML = '';

        rows.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.ruta}</td>
                <td>${row.operador}</td>
                <td>${row.unidad}</td>
                <td>${row.corrida}</td>
                <td>${row.turno}</td>
                <td>${row.horaIni}</td>
                <td>${row.horaFin}</td>
            `;
            tbody.appendChild(tr);
        });

        const totalPages = Math.ceil(allRows.length / rowsPerPage);
        document.getElementById('page-indicator').innerText = `Página ${currentPage + 1} de ${totalPages}`;
    }

    function nextPage() {
        const totalPages = Math.ceil(allRows.length / rowsPerPage);
        currentPage = (currentPage + 1) % totalPages;
        renderPage();
    }

    function showLoading() {
        document.getElementById('hora').classList.add('table-flash');
        document.getElementById('loading-indicator').style.display = 'block';
    }

    function hideLoading() {
        document.getElementById('hora').classList.remove('table-flash');
        document.getElementById('loading-indicator').style.display = 'none';
    }

    function startPageCycle() {
        setInterval(() => {
            showLoading();
            setTimeout(() => {
                nextPage();
                hideLoading();
            }, 1000);
        }, intervaloRotacion);
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderPage();
        startPageCycle();
    });
</script>
@endsection
