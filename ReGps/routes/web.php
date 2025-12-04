<?php

use Illuminate\Support\Facades\Route;

// SPA - Todas las rutas del frontend se manejan por Vue Router
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
