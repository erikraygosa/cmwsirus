<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageUploadController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/upload/', [ImageUploadController::class, 'upload']);
Route::get('/get/{path}', [ImageUploadController::class, 'getImage'])->where('path', '.*');
Route::delete('/delete/{path}', [ImageUploadController::class, 'deleteImage'])->where('path', '.*');

