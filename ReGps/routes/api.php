<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\DispositivoController;
use App\Http\Controllers\Api\UbicacionController;
use App\Http\Controllers\Api\AlertaController;
use App\Http\Controllers\Api\ZonaController;

// ============================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================
Route::post('auth/login', [AuthController::class, 'login']);

// ============================================
// RUTAS PROTEGIDAS (Requieren autenticación)
// ============================================
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/cambiar-contraseña', [AuthController::class, 'cambiarContraseña']);
    Route::post('auth/revocar-todos', [AuthController::class, 'revocarTodos']);
    
    // Usuarios (Solo Administrador)
    Route::middleware('role:Administrador')->group(function () {
        Route::apiResource('usuarios', UsuarioController::class);
    });
    
    // Empleados (Solo Administrador)
    Route::middleware('role:Administrador')->group(function () {
        Route::apiResource('empleados', EmpleadoController::class);
    });
    
    // Dispositivos
    // Empleados pueden ver solo sus dispositivos asignados
    Route::get('dispositivos/mis-dispositivos', [DispositivoController::class, 'misDispositivos']);
    
    // Solo Administrador tiene acceso completo
    Route::middleware('role:Administrador')->group(function () {
        Route::apiResource('dispositivos', DispositivoController::class);
    });
    
    // Ubicaciones (Todos pueden crear, solo admin puede ver todas)
    Route::post('ubicaciones', [UbicacionController::class, 'store']);
    Route::middleware('role:Administrador')->group(function () {
        Route::get('ubicaciones', [UbicacionController::class, 'index']);
        Route::get('ubicaciones/{id}', [UbicacionController::class, 'show']);
        Route::put('ubicaciones/{id}', [UbicacionController::class, 'update']);
        Route::delete('ubicaciones/{id}', [UbicacionController::class, 'destroy']);
        
        // Endpoints adicionales para el frontend
        Route::get('locations/current', [UbicacionController::class, 'getCurrentLocations']);
        Route::get('locations/history', [UbicacionController::class, 'getHistory']);
    });
    
    // Zonas (Solo Administrador puede crear/editar/eliminar)
    Route::get('zonas', [ZonaController::class, 'index']);
    Route::get('zonas/{id}', [ZonaController::class, 'show']);
    Route::post('zonas/verificar-ubicacion', [ZonaController::class, 'verificarUbicacion']);
    Route::get('zonas/{id}/historial', [ZonaController::class, 'historial']);
    
    Route::middleware('role:Administrador')->group(function () {
        Route::post('zonas', [ZonaController::class, 'store']);
        Route::put('zonas/{id}', [ZonaController::class, 'update']);
        Route::delete('zonas/{id}', [ZonaController::class, 'destroy']);
    });
    
    // Alertas (Todos pueden ver, solo admin puede modificar)
    Route::get('alertas', [AlertaController::class, 'index']);
    Route::get('alertas/{id}', [AlertaController::class, 'show']);
    
    Route::middleware('role:Administrador')->group(function () {
        Route::post('alertas', [AlertaController::class, 'store']);
        Route::put('alertas/{id}', [AlertaController::class, 'update']);
        Route::delete('alertas/{id}', [AlertaController::class, 'destroy']);
    });
});
