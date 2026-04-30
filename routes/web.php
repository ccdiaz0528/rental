<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('documento/contratos/{path}', function (string $path) {
    if (! Storage::disk('local')->exists($path)) {
        abort(404, 'File not found: '.$path);
    }
    $file = Storage::disk('local')->get($path);
    $mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        default => 'application/octet-stream',
    };

    return response($file, 200, ['Content-Type' => $mime, 'Content-Disposition' => 'inline; filename="'.basename($path).'"']);
})->where('path', '.+')->name('contrato.documento');
