<?php

use App\Http\Controllers\FileController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/upload');

// Страница загрузки
Route::get('/upload', [UploadController::class, 'create'])->name('upload.create');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

// CRUD-страница управления файлами
Route::get('/files', [FileController::class, 'index'])->name('files.index');
Route::get('/files/{file}/download', [FileController::class, 'download'])->name('files.download');
Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');

// Переключение языка интерфейса
Route::get('/locale/{locale}', LocaleController::class)
    ->whereIn('locale', array_keys(config('app.available_locales')))
    ->name('locale.switch');
