<?php
/**
 * Script de Verificación de Zonas
 * 
 * Este script consulta la base de datos de zonas y muestra 
 * las coordenadas guardadas en formato legible
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "═══════════════════════════════════════════════════════════════\n";
echo "   🗺️  VERIFICACIÓN DE ZONAS DE GEOFENCING  🗺️\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

try {
    // Obtener todas las zonas
    $zonas = DB::table('zonas')->get();
    
    if ($zonas->isEmpty()) {
        echo "⚠️  No se encontraron zonas en la base de datos.\n";
        exit;
    }
    
    echo "📊 Total de zonas encontradas: " . $zonas->count() . "\n\n";
    
    foreach ($zonas as $index => $zona) {
        echo "┌─────────────────────────────────────────────────────────────┐\n";
        echo "│ ZONA #" . ($index + 1) . "\n";
        echo "├─────────────────────────────────────────────────────────────┤\n";
        
        // Información básica
        echo "│ 📍 Nombre: " . $zona->Nombre . "\n";
        echo "│ 🏷️  Tipo de Zona: " . $zona->TipoZona . "\n";
        echo "│ 📐 Tipo de Geometría: " . $zona->TipoGeometria . "\n";
        echo "│ 🟢 Estado: " . ($zona->Estado === 'Activo' ? '✅ Activo' : '❌ Inactivo') . "\n";
        
        // Horario (si existe)
        if ($zona->HorarioInicio && $zona->HorarioFin) {
            echo "│ ⏰ Horario: " . $zona->HorarioInicio . " - " . $zona->HorarioFin . "\n";
        } else {
            echo "│ ⏰ Horario: 🌙 24/7 (Permanente)\n";
        }
        
        // Descripción (si existe)
        if ($zona->Descripcion) {
            echo "│ 📝 Descripción: " . $zona->Descripcion . "\n";
        }
        
        echo "├─────────────────────────────────────────────────────────────┤\n";
        echo "│ COORDENADAS:\n";
        echo "├─────────────────────────────────────────────────────────────┤\n";
        
        if ($zona->TipoGeometria === 'Circulo') {
            // Zona circular
            echo "│ 🔵 Círculo:\n";
            echo "│    Centro:\n";
            echo "│       Latitud:  " . ($zona->Latitud ?? '❌ NO GUARDADA') . "\n";
            echo "│       Longitud: " . ($zona->Longitud ?? '❌ NO GUARDADA') . "\n";
            echo "│    Radio: " . ($zona->Radio ?? '❌ NO GUARDADO') . " metros\n";
            
            // Verificar si las coordenadas están guardadas
            if (!$zona->Latitud || !$zona->Longitud) {
                echo "│ ⚠️  ADVERTENCIA: ¡Coordenadas del centro NO guardadas!\n";
            }
            if (!$zona->Radio) {
                echo "│ ⚠️  ADVERTENCIA: ¡Radio NO guardado!\n";
            }
            
        } elseif ($zona->TipoGeometria === 'Poligono') {
            // Zona poligonal
            echo "│ 🔶 Polígono:\n";
            
            if ($zona->Coordenadas) {
                $coordenadas = json_decode($zona->Coordenadas, true);
                
                if (is_array($coordenadas) && count($coordenadas) > 0) {
                    echo "│    Número de vértices: " . count($coordenadas) . "\n";
                    echo "│    Vértices:\n";
                    
                    foreach ($coordenadas as $i => $punto) {
                        $lat = $punto['lat'] ?? 'N/A';
                        $lng = $punto['lng'] ?? 'N/A';
                        echo "│       Punto " . ($i + 1) . ": (" . $lat . ", " . $lng . ")\n";
                    }
                } else {
                    echo "│ ⚠️  ADVERTENCIA: Array de coordenadas vacío o inválido\n";
                    echo "│    Datos crudos: " . ($zona->Coordenadas ?? 'NULL') . "\n";
                }
            } else {
                echo "│ ❌ ERROR: ¡Campo Coordenadas está VACÍO!\n";
            }
        }
        
        echo "└─────────────────────────────────────────────────────────────┘\n\n";
    }
    
    // Resumen
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📈 RESUMEN:\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    $circulos = $zonas->where('TipoGeometria', 'Circulo')->count();
    $poligonos = $zonas->where('TipoGeometria', 'Poligono')->count();
    $activas = $zonas->where('Estado', 'Activo')->count();
    $inactivas = $zonas->where('Estado', 'Inactivo')->count();
    $checkpoints = $zonas->where('TipoZona', 'Checkpoint')->count();
    $zonasPermitidas = $zonas->where('TipoZona', 'Zona Permitida')->count();
    $zonasRestringidas = $zonas->where('TipoZona', 'Zona Restringida')->count();
    
    echo "🔵 Círculos: " . $circulos . "\n";
    echo "🔶 Polígonos: " . $poligonos . "\n";
    echo "✅ Activas: " . $activas . "\n";
    echo "❌ Inactivas: " . $inactivas . "\n";
    echo "📍 Checkpoints: " . $checkpoints . "\n";
    echo "🟢 Zonas Permitidas: " . $zonasPermitidas . "\n";
    echo "🔴 Zonas Restringidas: " . $zonasRestringidas . "\n";
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "✅ Verificación completada exitosamente\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    
    // Verificar integridad de datos
    echo "\n🔍 VERIFICACIÓN DE INTEGRIDAD:\n";
    echo "───────────────────────────────────────────────────────────────\n";
    
    $problemasEncontrados = false;
    
    foreach ($zonas as $zona) {
        $problemas = [];
        
        if ($zona->TipoGeometria === 'Circulo') {
            if (!$zona->Latitud || !$zona->Longitud) {
                $problemas[] = "Falta latitud/longitud del centro";
            }
            if (!$zona->Radio) {
                $problemas[] = "Falta radio";
            }
        } elseif ($zona->TipoGeometria === 'Poligono') {
            if (!$zona->Coordenadas) {
                $problemas[] = "Campo Coordenadas vacío";
            } else {
                $coords = json_decode($zona->Coordenadas, true);
                if (!is_array($coords) || count($coords) < 3) {
                    $problemas[] = "Polígono necesita al menos 3 puntos";
                }
            }
        }
        
        if (!empty($problemas)) {
            $problemasEncontrados = true;
            echo "⚠️  Zona '{$zona->Nombre}':\n";
            foreach ($problemas as $problema) {
                echo "   - " . $problema . "\n";
            }
        }
    }
    
    if (!$problemasEncontrados) {
        echo "✅ Todas las zonas tienen datos completos\n";
    }
    
    echo "───────────────────────────────────────────────────────────────\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stacktrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
