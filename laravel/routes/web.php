<?php

use App\Infrastructure\Http\Controllers\FileDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Private file download (signed URL required)
Route::get('/files/download/{path}', [FileDownloadController::class, 'download'])
    ->name('files.download')
    ->middleware('signed');

// Alternative streaming endpoint (when X-Accel-Redirect is not available)
Route::get('/files/stream/{path}', [FileDownloadController::class, 'stream'])
    ->name('files.stream')
    ->middleware('signed');
