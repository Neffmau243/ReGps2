<?php

namespace App\Services;

use App\Models\Ubicacion;
use App\Models\Dispositivo;
use Carbon\Carbon;

class RouteService
{
    /**
     * Detectar inicio automático de ruta
     * Se considera inicio cuando el dispositivo empieza a moverse después de estar detenido
     */
    public function detectarInicioRuta(int $dispositivoId): ?array
    {
        $ultimasUbicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->orderBy('FechaHora', 'desc')
            ->limit(5)
            ->get();
        
        if ($ultimasUbicaciones->count() < 2) {
            return null;
        }
        
        // Si las últimas 2 ubicaciones tienen velocidad > 0, está en movimiento
        $enMovimiento = $ultimasUbicaciones->take(2)->every(fn($u) => $u->Velocidad > 5);
        
        if ($enMovimiento) {
            return [
                'inicio' => true,
                'ubicacion_inicio' => $ultimasUbicaciones->last(),
                'timestamp' => $ultimasUbicaciones->last()->FechaHora
            ];
        }
        
        return null;
    }
    
    /**
     * Detectar fin automático de ruta
     * Se considera fin cuando el dispositivo se detiene por más de X minutos
     */
    public function detectarFinRuta(int $dispositivoId, int $minutosDetenido = 10): ?array
    {
        $ultimasUbicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->orderBy('FechaHora', 'desc')
            ->limit(10)
            ->get();
        
        if ($ultimasUbicaciones->count() < 3) {
            return null;
        }
        
        // Verificar si está detenido (velocidad < 5 km/h)
        $detenido = $ultimasUbicaciones->take(3)->every(fn($u) => $u->Velocidad < 5);
        
        if ($detenido) {
            $tiempoDetenido = $ultimasUbicaciones->first()->FechaHora
                ->diffInMinutes($ultimasUbicaciones->skip(2)->first()->FechaHora);
            
            if ($tiempoDetenido >= $minutosDetenido) {
                return [
                    'fin' => true,
                    'ubicacion_fin' => $ultimasUbicaciones->first(),
                    'timestamp' => $ultimasUbicaciones->first()->FechaHora,
                    'tiempo_detenido' => $tiempoDetenido
                ];
            }
        }
        
        return null;
    }
    
    /**
     * Calcular distancia total de una ruta usando fórmula Haversine
     */
    public function calcularDistanciaTotal(int $dispositivoId, Carbon $inicio, Carbon $fin): float
    {
        $ubicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->whereBetween('FechaHora', [$inicio, $fin])
            ->orderBy('FechaHora', 'asc')
            ->get();
        
        if ($ubicaciones->count() < 2) {
            return 0;
        }
        
        $distanciaTotal = 0;
        
        for ($i = 0; $i < $ubicaciones->count() - 1; $i++) {
            $distanciaTotal += $this->calcularDistanciaHaversine(
                $ubicaciones[$i]->Latitud,
                $ubicaciones[$i]->Longitud,
                $ubicaciones[$i + 1]->Latitud,
                $ubicaciones[$i + 1]->Longitud
            );
        }
        
        return round($distanciaTotal / 1000, 2); // Convertir a kilómetros
    }
    
    /**
     * Calcular duración de una ruta
     */
    public function calcularDuracion(Carbon $inicio, Carbon $fin): array
    {
        $minutos = $inicio->diffInMinutes($fin);
        $horas = floor($minutos / 60);
        $minutosRestantes = $minutos % 60;
        
        return [
            'total_minutos' => $minutos,
            'horas' => $horas,
            'minutos' => $minutosRestantes,
            'formato' => sprintf('%02d:%02d', $horas, $minutosRestantes)
        ];
    }
    
    /**
     * Calcular velocidad promedio
     */
    public function calcularVelocidadPromedio(int $dispositivoId, Carbon $inicio, Carbon $fin): float
    {
        $velocidadPromedio = Ubicacion::where('DispositivoID', $dispositivoId)
            ->whereBetween('FechaHora', [$inicio, $fin])
            ->whereNotNull('Velocidad')
            ->where('Velocidad', '>', 0)
            ->avg('Velocidad');
        
        return round($velocidadPromedio ?? 0, 2);
    }
    
    /**
     * Calcular velocidad máxima
     */
    public function calcularVelocidadMaxima(int $dispositivoId, Carbon $inicio, Carbon $fin): float
    {
        $velocidadMaxima = Ubicacion::where('DispositivoID', $dispositivoId)
            ->whereBetween('FechaHora', [$inicio, $fin])
            ->whereNotNull('Velocidad')
            ->max('Velocidad');
        
        return round($velocidadMaxima ?? 0, 2);
    }
    
    /**
     * Estimar consumo de batería basado en distancia y tiempo
     * Fórmula simplificada: 1% por cada 10 minutos de uso activo
     */
    public function estimarConsumoBateria(float $distanciaKm, int $minutos): array
    {
        // Consumo base: 1% cada 10 minutos
        $consumoPorTiempo = ($minutos / 10) * 1;
        
        // Consumo adicional por distancia: 0.5% cada 5 km
        $consumoPorDistancia = ($distanciaKm / 5) * 0.5;
        
        $consumoTotal = $consumoPorTiempo + $consumoPorDistancia;
        
        return [
            'consumo_estimado' => round($consumoTotal, 1),
            'consumo_por_tiempo' => round($consumoPorTiempo, 1),
            'consumo_por_distancia' => round($consumoPorDistancia, 1)
        ];
    }
    
    /**
     * Obtener estadísticas completas de una ruta
     */
    public function obtenerEstadisticasRuta(int $dispositivoId, Carbon $inicio, Carbon $fin): array
    {
        $distancia = $this->calcularDistanciaTotal($dispositivoId, $inicio, $fin);
        $duracion = $this->calcularDuracion($inicio, $fin);
        $velocidadPromedio = $this->calcularVelocidadPromedio($dispositivoId, $inicio, $fin);
        $velocidadMaxima = $this->calcularVelocidadMaxima($dispositivoId, $inicio, $fin);
        $bateria = $this->estimarConsumoBateria($distancia, $duracion['total_minutos']);
        
        return [
            'dispositivo_id' => $dispositivoId,
            'inicio' => $inicio->format('Y-m-d H:i:s'),
            'fin' => $fin->format('Y-m-d H:i:s'),
            'distancia_km' => $distancia,
            'duracion' => $duracion,
            'velocidad_promedio_kmh' => $velocidadPromedio,
            'velocidad_maxima_kmh' => $velocidadMaxima,
            'consumo_bateria' => $bateria
        ];
    }
    
    /**
     * Fórmula Haversine para calcular distancia entre dos puntos GPS
     */
    private function calcularDistanciaHaversine($lat1, $lon1, $lat2, $lon2): float
    {
        $radioTierra = 6371000; // metros
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $radioTierra * $c; // metros
    }
    
    /**
     * Comparar ruta actual con rutas anteriores
     */
    public function compararConRutasAnteriores(int $dispositivoId, array $rutaActual): array
    {
        // Obtener rutas de los últimos 30 días
        $fechaLimite = Carbon::now()->subDays(30);
        
        $rutasAnteriores = Ubicacion::where('DispositivoID', $dispositivoId)
            ->where('FechaHora', '>=', $fechaLimite)
            ->selectRaw('DATE(FechaHora) as fecha, 
                         COUNT(*) as puntos,
                         AVG(Velocidad) as velocidad_promedio,
                         MAX(Velocidad) as velocidad_maxima')
            ->groupBy('fecha')
            ->get();
        
        if ($rutasAnteriores->isEmpty()) {
            return ['comparacion' => 'Sin datos históricos'];
        }
        
        $promedioHistorico = [
            'velocidad_promedio' => $rutasAnteriores->avg('velocidad_promedio'),
            'velocidad_maxima' => $rutasAnteriores->avg('velocidad_maxima')
        ];
        
        return [
            'ruta_actual' => $rutaActual,
            'promedio_historico' => $promedioHistorico,
            'diferencia_velocidad' => round($rutaActual['velocidad_promedio_kmh'] - $promedioHistorico['velocidad_promedio'], 2),
            'total_rutas_comparadas' => $rutasAnteriores->count()
        ];
    }
}
