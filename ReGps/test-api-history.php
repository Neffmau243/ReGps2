<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Dispositivo;
use App\Models\Ubicacion;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "          TEST API /locations/history - ReGPS                  \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

$dispositivos = Dispositivo::with(['empleado.usuario'])->get();

foreach ($dispositivos as $device) {
    echo "\n";
    echo "🔍 DISPOSITIVO #" . $device->DispositivoID . " - " . ($device->Modelo ?? 'N/A') . "\n";
    echo "───────────────────────────────────────────────────────────────\n";
    
    // Simular la consulta exacta del controlador
    $query = Ubicacion::with(['dispositivo.empleado.usuario'])
        ->where('DispositivoID', $device->DispositivoID)
        ->orderBy('FechaHora', 'desc')
        ->limit(5);
    
    $ubicaciones = $query->get();
    
    if ($ubicaciones->isEmpty()) {
        echo "   ❌ Sin ubicaciones\n";
        continue;
    }
    
    echo "   📊 Ubicaciones encontradas: " . $ubicaciones->count() . "\n\n";
    
    // Simular exactamente lo que hace el controlador
    $firstUbicacion = $ubicaciones->first();
    $dispositivo = $firstUbicacion->dispositivo;
    $empleado = $dispositivo->empleado ?? null;
    $usuario = $empleado->usuario ?? null;
    
    echo "   📱 Dispositivo:\n";
    echo "      - ID: " . ($dispositivo->DispositivoID ?? 'NULL') . "\n";
    echo "      - Modelo: " . ($dispositivo->Modelo ?? 'NULL') . "\n";
    echo "      - IMEI: " . ($dispositivo->IMEI ?? 'NULL') . "\n";
    echo "\n";
    
    echo "   👤 Empleado:\n";
    if ($empleado) {
        echo "      - ID: " . $empleado->EmpleadoID . "\n";
        echo "      - Nombre: " . ($empleado->Nombre ?? 'NULL') . "\n";
        echo "      - Apellido: " . ($empleado->Apellido ?? 'NULL') . "\n";
        echo "      - UsuarioID: " . ($empleado->UsuarioID ?? 'NULL') . "\n";
    } else {
        echo "      - ⚠️  NULL (Sin empleado asignado)\n";
    }
    echo "\n";
    
    echo "   👥 Usuario:\n";
    if ($usuario) {
        echo "      - ID: " . $usuario->id . "\n";
        echo "      - Nombre: " . ($usuario->Nombre ?? $usuario->name ?? 'NULL') . "\n";
        echo "      - Email: " . ($usuario->email ?? 'NULL') . "\n";
    } else {
        echo "      - ⚠️  NULL (Sin usuario vinculado)\n";
    }
    echo "\n";
    
    // Calcular el nombre del usuario como lo hace el controlador ANTES del fix
    echo "   🧪 PRUEBA ACCESO A DATOS:\n";
    
    try {
        // Intento ORIGINAL (el que causaba error)
        $userName_OLD = $usuario->Nombre ?? 'Usuario Desconocido';
        echo "      ✅ OLD: \$usuario->Nombre = " . $userName_OLD . "\n";
    } catch (\Exception $e) {
        echo "      ❌ OLD: Error - " . $e->getMessage() . "\n";
    }
    
    // Intento NUEVO (el fix)
    $userName_NEW = 'Usuario Desconocido';
    if ($empleado) {
        $userName_NEW = $empleado->Nombre . ($empleado->Apellido ? ' ' . $empleado->Apellido : '');
    } elseif ($usuario) {
        $userName_NEW = $usuario->Nombre ?? $usuario->name ?? 'Usuario Desconocido';
    }
    echo "      ✅ NEW: userName = " . $userName_NEW . "\n";
    
    echo "\n";
    
    // Mostrar JSON de respuesta simulada
    $locations = $ubicaciones->map(function($ubicacion) {
        return [
            'latitude' => (float) $ubicacion->Latitud,
            'longitude' => (float) $ubicacion->Longitud,
            'timestamp' => $ubicacion->FechaHora->toIso8601String(),
        ];
    });
    
    $response = [
        'device' => [
            'id' => $dispositivo->DispositivoID,
            'name' => $dispositivo->Modelo ?? 'Dispositivo ' . $dispositivo->DispositivoID,
            'Modelo' => $dispositivo->Modelo,
            'user_name' => $userName_NEW,
            'EmpleadoNombre' => $userName_NEW,
        ],
        'locations' => $locations->toArray(),
        'statistics' => [
            'total_points' => $ubicaciones->count(),
            'distance_km' => 0,
            'duration_minutes' => 0,
        ]
    ];
    
    echo "   📝 RESPUESTA API (preview):\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    echo "───────────────────────────────────────────────────────────────\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "                    ✅ TEST COMPLETO                           \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
