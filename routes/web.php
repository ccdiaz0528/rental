<?php

use App\Models\Contrato;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('documento/contratos/{path}', function (string $path) {
    if (! Auth::check()) {
        abort(401, 'Unauthorized');
    }

    if (! Storage::disk('local')->exists($path)) {
        abort(404, 'File not found: '.$path);
    }

    $user = Auth::user();

    if (! $user->hasRole('admin')) {
        $contrato = Contrato::where('documento', $path)->first();

        if (! $contrato || (int) $contrato->user_id !== (int) $user->id) {
            abort(403, 'Forbidden');
        }
    }

    $mime = match (pathinfo($path, PATHINFO_EXTENSION)) {
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'jpg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        default => 'application/octet-stream',
    };

    return response()->stream(function () use ($path) {
        $stream = Storage::disk('local')->readStream($path);
        if ($stream) {
            fpassthru($stream);
            fclose($stream);
        }
    }, 200, [
        'Content-Type' => $mime,
        'Content-Disposition' => 'inline; filename="'.basename($path).'"',
    ]);
})->where('path', '.+')->name('contrato.documento');
