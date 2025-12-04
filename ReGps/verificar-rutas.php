<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Dispositivo;
use App\Models\Ubicacion;
use App\Models\Empleado;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "       VERIFICACIÓN DE RUTAS Y UBICACIONES - ReGPS            \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    // 1. Verificar dispositivos
    $dispositivos = Dispositivo::with('empleado')->get();
    
    echo "📱 DISPOSITIVOS REGISTRADOS: " . $dispositivos->count() . "\n\n";
    
    if ($dispositivos->isEmpty()) {
        echo "❌ No hay dispositivos registrados.\n\n";
        exit(1);
    }
    
    echo "───────────────────────────────────────────────────────────────\n";
    
    foreach ($dispositivos as $device) {
        echo "\n";
        echo "📱 DISPOSITIVO #" . $device->DispositivoID . "\n";
        echo "───────────────────────────────────────────────────────────────\n";
        echo "   📝 Modelo:         " . ($device->Modelo ?? 'N/A') . "\n";
        echo "   🏷️  Marca:          " . ($device->Marca ?? 'N/A') . "\n";
        echo "   📟 IMEI:           " . $device->IMEI . "\n";
        echo "   📊 Estado:         " . $device->Estado . "\n";
        
        if ($device->empleado) {
            echo "   👤 Empleado:       " . $device->empleado->Nombre . " " . $device->empleado->Apellido . "\n";
        } else {
            echo "   👤 Empleado:       Sin asignar\n";
        }
        
        // Contar ubicaciones de este dispositivo
        $ubicaciones = Ubicacion::where('DispositivoID', $device->DispositivoID)
            ->orderBy('FechaHora', 'desc')
            ->get();
        
        echo "   📍 Ubicaciones:    " . $ubicaciones->count() . " registros\n";
        
        if ($ubicaciones->count() > 0) {
            $ultima = $ubicaciones->first();
            echo "\n   🕒 ÚLTIMA UBICACIÓN:\n";
            echo "      📅 Fecha:       " . $ultima->FechaHora . "\n";
            echo "      🌍 Latitud:     " . $ultima->Latitud . "\n";
            echo "      🌍 Longitud:    " . $ultima->Longitud . "\n";
            echo "      ⚡ Velocidad:   " . ($ultima->Velocidad ?? 'N/A') . " km/h\n";
            echo "      🎯 Precisión:   " . ($ultima->Precision ?? 'N/A') . " m\n";
            echo "      🧭 Dirección:   " . ($ultima->Direccion ?? 'N/A') . "\n";
            
            // Mostrar rango de fechas
            $primera = $ubicaciones->last();
            echo "\n   📊 RANGO DE DATOS:\n";
            echo "      🟢 Primera:    " . $primera->FechaHora . "\n";
            echo "      🔴 Última:     " . $ultima->FechaHora . "\n";
            
            // Calcular días de datos
            $inicio = new DateTime($primera->FechaHora);
            $fin = new DateTime($ultima->FechaHora);
            $dias = $inicio->diff($fin)->days;
            echo "      📆 Período:    " . $dias . " días\n";
            
            // Mostrar muestra de las últimas 5 ubicaciones
            echo "\n   📋 ÚLTIMAS 5 UBICACIONES:\n";
            foreach ($ubicaciones->take(5) as $index => $loc) {
                echo "      " . ($index + 1) . ". " . $loc->FechaHora . " | ";
                echo "Lat: " . number_format($loc->Latitud, 6) . ", ";
                echo "Lng: " . number_format($loc->Longitud, 6) . " | ";
                echo "Vel: " . ($loc->Velocidad ?? '0') . " km/h\n";
            }
        } else {
            echo "\n   ⚠️  SIN DATOS DE UBICACIÓN\n";
        }
        
        echo "───────────────────────────────────────────────────────────────\n";
    }
    
    // 2. Resumen general de ubicaciones
    echo "\n\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "                    RESUMEN GENERAL                            \n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    $totalUbicaciones = Ubicacion::count();
    echo "\n📊 TOTAL DE UBICACIONES: " . number_format($totalUbicaciones) . "\n";
    
    if ($totalUbicaciones > 0) {
        // Ubicación más antigua
        $masAntigua = Ubicacion::orderBy('FechaHora', 'asc')->first();
        echo "🟢 Más antigua:     " . $masAntigua->FechaHora . "\n";
        
        // Ubicación más reciente
        $masReciente = Ubicacion::orderBy('FechaHora', 'desc')->first();
        echo "🔴 Más reciente:    " . $masReciente->FechaHora . "\n";
        
        // Rango de coordenadas
        $minLat = Ubicacion::min('Latitud');
        $maxLat = Ubicacion::max('Latitud');
        $minLng = Ubicacion::min('Longitud');
        $maxLng = Ubicacion::max('Longitud');
        
        echo "\n🗺️  ÁREA GEOGRÁFICA:\n";
        echo "   Latitud:  " . number_format($minLat, 6) . " a " . number_format($maxLat, 6) . "\n";
        echo "   Longitud: " . number_format($minLng, 6) . " a " . number_format($maxLng, 6) . "\n";
        
        // Velocidad promedio
        $velPromedio = Ubicacion::avg('Velocidad');
        if ($velPromedio) {
            echo "\n⚡ Velocidad promedio: " . number_format($velPromedio, 2) . " km/h\n";
        }
    }
    
    // 3. Verificar estructura de datos para historial
    echo "\n\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "          VERIFICACIÓN PARA API /locations/history            \n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    foreach ($dispositivos->take(3) as $device) {
        echo "\n📱 Dispositivo #" . $device->DispositivoID . " (" . ($device->Modelo ?? 'N/A') . ")\n";
        
        $ubicaciones = Ubicacion::where('DispositivoID', $device->DispositivoID)
            ->orderBy('FechaHora', 'desc')
            ->limit(1)
            ->get();
        
        if ($ubicaciones->count() > 0) {
            $ub = $ubicaciones->first();
            
            // Simular respuesta de API
            $apiResponse = [
                'device' => [
                    'name' => $device->Modelo ?? 'N/A',
                    'Modelo' => $device->Modelo,
                    'user_name' => $device->empleado ? $device->empleado->Nombre . ' ' . $device->empleado->Apellido : 'N/A',
                    'EmpleadoNombre' => $device->empleado ? $device->empleado->Nombre . ' ' . $device->empleado->Apellido : null,
                ],
                'locations' => [
                    [
                        'latitude' => $ub->Latitud,
                        'Latitud' => $ub->Latitud,
                        'longitude' => $ub->Longitud,
                        'Longitud' => $ub->Longitud,
                        'timestamp' => $ub->FechaHora,
                        'FechaHora' => $ub->FechaHora,
                        'speed' => $ub->Velocidad,
                        'Velocidad' => $ub->Velocidad,
                    ]
                ],
                'statistics' => [
                    'total_points' => $ubicaciones->count(),
                    'distance_km' => 0,
                    'duration_minutes' => 0,
                ]
            ];
            
            echo "   ✅ Estructura de datos válida\n";
            echo "   📝 JSON Preview:\n";
            echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "   ❌ Sin ubicaciones - causará error en frontend\n";
        }
    }
    
    echo "\n\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "                    ✅ VERIFICACIÓN COMPLETA                   \n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\n";
    echo "🔍 Stack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n";
    exit(1);
}
