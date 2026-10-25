<?php

use App\Http\Controllers\CombustibleController;
use Illuminate\Support\Facades\Route;

// Este archivo se incluye dentro del grupo auth:sanctum + auth_session + verified.
Route::prefix('combustible')->name('combustible.')->middleware(['can:combustible_ver', \App\Http\Middleware\CombustibleUi::class])->controller(CombustibleController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/operaciones/reintentar', 'resume')->name('resume');
    Route::get('/unidades', 'units')->name('units');
    Route::get('/cotizar', 'quote')->name('quote');
    Route::get('/crear', 'create')->middleware('can:combustible_crear')->name('create');
    Route::post('/cargas', 'store')->middleware('can:combustible_crear')->name('store');
    Route::get('/cargas/{id}/editar', 'edit')->whereNumber('id')->middleware('can:combustible_editar')->name('edit');
    Route::put('/cargas/{id}', 'update')->whereNumber('id')->middleware('can:combustible_editar')->name('update');
    Route::post('/cargas/{id}/cancelar', 'cancel')->whereNumber('id')->middleware('can:combustible_cancelar')->name('cancel');
    Route::get('/exportar/{format}', 'export')->middleware('can:combustible_exportar')->name('export');
    Route::get('/auditoria', 'audit')->middleware('can:combustible_auditoria')->name('audit');
    Route::middleware('can:combustible_precios')->group(function () {
        Route::get('/precios', 'prices')->name('prices');
        Route::get('/precios/revisar', 'reviewPrice')->name('prices.review');
        Route::post('/precios', 'savePrice')->name('prices.save');
        Route::delete('/precios/{date}', 'deletePrice')->name('prices.delete');
        Route::post('/aviso', 'notice')->name('notice');
        Route::post('/aviso/omitir', 'omitNotice')->name('notice.omit');
    });
});
