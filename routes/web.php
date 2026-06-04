<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('documento/contratos/{path}', [DocumentController::class, 'show'])
    ->where('path', '.+')
    ->name('contrato.documento');
