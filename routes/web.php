<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\HorarioOperadorController;
use App\Http\Controllers\TblaccidenteController;
use App\Http\Controllers\accidenteImagenController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\HorariodiaController;
use App\Http\Controllers\HorariodiasigController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\resetController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\ExpedienteController;

/* ================= Raíz / Login (pública) ================= */
Route::get('/', function () {
    return view('auth.login');
});

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});

Route::get('/horariodia/tv', [HorarioDiaController::class, 'tv'])->name('horario.tv');

/* ============================================================
   TODO lo demás protegido con Jetstream (auth + verified)
   ============================================================ */
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::resource('/dashboard', DashboardController::class)->names('dashboard');

    Route::resource('reset', resetController::class)->middleware('can:horararios')->names('reset');
    Route::resource('horarios', Horariocontroller::class)->middleware('can:horararios')->names('horarios');
    Route::resource('horariosope', HorarioOperadorController::class)->middleware('can:horarariosope')->names('horarariosope');

    Route::match(['get', 'post'], '/accidentes/generarpdf', [App\Http\Controllers\TblaccidenteController::class, 'generarpdf'])
        ->name('accidentes.generarpdf');

    Route::resource('users',    UserController::class)      ->middleware('can:adminusers')->names('users');
    Route::resource('roles',    RoleController::class)      ->middleware('can:adminusers')->names('roles');
    Route::resource('permisos', PermissionController::class)->middleware('can:adminusers')->names('permisos')->only(['index','create','store','destroy']);
    Route::resource('accidentes', TblaccidenteController::class)->middleware('can:accidentes')->names('accidentes');

    Route::match(['get', 'post'], '/accidentes/actualizarestado', [App\Http\Controllers\TblaccidenteController::class, 'cambiarEstado'])
        ->name('accidentes.cambiarEstado');

    Route::get('/registro/{id}/editar/{tipo?}', [TblaccidenteController::class, 'mostrar'])
        ->name('registro.mostrar');
    Route::post('/registro/{id}/actualizar/{tipo?}', [TblaccidenteController::class, 'actualizar'])
        ->name('registro.actualizar');

        Route::get('/accidentes/zonas/{idRuta}', [TblaccidenteController::class, 'getZonasPorRuta'])->name('accidentes.zonas');   

    Route::post('update-record', [Horariocontroller::class, 'updateRecord'])->name('update-record');

    Route::post('/imagenesAccidente', [App\Http\Controllers\accidenteImagenController::class, 'store'])
        ->name('accidente.imagenStore');

    /* ---- MÓDULO IMÁGENES ---- */
    Route::prefix('imagenes')->name('imagenes.')->group(function () {

        Route::get('/', [ImagenController::class, 'index'])->name('index');
        Route::get('/create', [ImagenController::class, 'create'])->name('create');

        Route::post('/ajax/crear-abierto', [ImagenController::class, 'ajaxCrearAbierto'])->name('ajax.crearAbierto');
        Route::post('/cerrar/{folio}',     [ImagenController::class, 'cerrar'])->name('cerrar');
        Route::delete('/folio/{folio}',    [ImagenController::class, 'eliminarFolio'])->name('folio.eliminar');

        Route::get('/ajax/estado-unidad/{idUnidad}', [ImagenController::class, 'ajaxEstadoUnidad'])->name('ajax.estadoUnidad');

        Route::post('/adjuntos',                  [ImagenController::class, 'store'])->name('adjuntos.store');
        Route::delete('/adjuntos/{id}',           [ImagenController::class, 'eliminarAdjunto'])->name('adjuntos.destroy');
        Route::patch('/adjuntos/{id}/comentario', [ImagenController::class, 'actualizarComentario'])->name('adjuntos.updateComentario');
        Route::patch('/adjuntos/{id}/confirmar',  [ImagenController::class, 'confirmarAdjunto'])->name('adjuntos.confirmar');
        Route::patch('/adjuntos/{id}/revertir',   [ImagenController::class, 'volverAdjuntoADocumentado'])->name('adjuntos.revertir');
        Route::patch('/adjuntos/{id}/markup',     [ImagenController::class, 'actualizarMarkup'])->name('adjuntos.markup');

        Route::post('/adjuntos/{id}/evidencia',   [ImagenController::class, 'subirEvidencia'])->name('adjuntos.evidencia.store');
        Route::delete('/adjuntos/{id}/evidencia', [ImagenController::class, 'eliminarEvidencia'])->name('adjuntos.evidencia.destroy');

        Route::get('/dt',                         [ImagenController::class, 'dt'])->name('dt');
        Route::get('/ajax/adjuntos-list/{folio}', [ImagenController::class, 'ajaxAdjuntosList'])->name('ajax.adjuntosList');

        Route::patch('/folio/{folio}/estatus',    [ImagenController::class, 'cambiarEstatusFolio'])->name('folio.estatus');
        Route::patch('/folio/{folio}/comentario', [ImagenController::class, 'actualizarComentarioFolio'])->name('folio.comentario');

        Route::get('/{folio}/edit',  [ImagenController::class, 'edit'])->name('edit');
        Route::post('/{folio}/edit', [ImagenController::class, 'updateEdit'])->name('edit.update');
    });

    /* ---- MÓDULO EXPEDIENTES — lista unificada catempleados + catoperadores ---- */
    Route::prefix('expedientes')->name('expedientes.')->middleware('can:expedientes')->group(function () {

        // ⚠️ Rutas estáticas SIEMPRE antes de /{id}
        Route::get('/masivo/zip',            [ExpedienteController::class, 'exportMasivo'])     ->name('masivo.zip');
        Route::post('/masivo/drive',         [ExpedienteController::class, 'syncDrive'])       ->name('drive.sync');
        Route::get('/masivo/drive/{syncId}', [ExpedienteController::class, 'syncDriveStatus']) ->name('drive.status');

        Route::get('/',                      [ExpedienteController::class, 'index'])        ->name('index');
        Route::delete('/documentos/{idAdj}', [ExpedienteController::class, 'destroy'])      ->name('destroy');

        // Registros provenientes de catoperadores (mysql3) — rutas /operador/* antes de /{id}
        Route::get('/operador/{id}',             [ExpedienteController::class, 'showOperador'])           ->name('operador.show');
        Route::post('/operador/{id}/documentos', [ExpedienteController::class, 'storeOperador'])          ->name('operador.store');
        Route::get('/operador/{id}/zip',         [ExpedienteController::class, 'downloadZipOperador'])    ->name('operador.zip');
        Route::post('/operador/{id}/envio',      [ExpedienteController::class, 'toggleEnvioOperador'])    ->name('operador.envio.toggle');
        Route::post('/operador/{id}/vigencia',   [ExpedienteController::class, 'updateVigenciaOperador'])->name('operador.vigencia.update');
        Route::patch('/operador/{id}/curp',      [ExpedienteController::class, 'updateCurpOperador'])    ->name('operador.curp.update');
        Route::patch('/operador/{id}/nombre',    [ExpedienteController::class, 'updateNombreOperador'])  ->name('operador.nombre.update');
        Route::post('/operador/{id}/baja',       [ExpedienteController::class, 'darDeBajaOperador'])     ->name('operador.baja');

        // Registros de catempleados (default)
        Route::get('/{id}',                  [ExpedienteController::class, 'show'])         ->name('show');
        Route::post('/{id}/documentos',      [ExpedienteController::class, 'store'])        ->name('store');
        Route::get('/{id}/zip',              [ExpedienteController::class, 'downloadZip'])  ->name('zip');
        Route::post('/{id}/envio',           [ExpedienteController::class, 'toggleEnvio'])  ->name('envio.toggle');
        Route::post('/{id}/vigencia',        [ExpedienteController::class, 'updateVigencia'])->name('vigencia.update');
        Route::patch('/{id}/curp',           [ExpedienteController::class, 'updateCurp'])   ->name('curp.update');
        Route::patch('/{id}/nombre',         [ExpedienteController::class, 'updateNombre']) ->name('nombre.update');
        Route::post('/{id}/baja',            [ExpedienteController::class, 'darDeBaja'])    ->name('baja');
    });

});