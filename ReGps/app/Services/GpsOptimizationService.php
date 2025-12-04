<?php

namespace App\Services;

use App\Models\Ubicacion;
use Carbon\Carbon;

class GpsOptimizationService
{
    const ACCURACY_MAXIMA = 50; // metros
    const VELOCIDAD_MAXIMA_REALISTA = 200; // km/h
    const DISTANCIA_MAXIMA_SALTO = 500; // metros en 1 segundo
    
    /**
     * Validar si una ubicación GPS es válida
     */
    public function esUbicacionValida(array $datos): array
    {
        $errores = [];
        
        // Validar coordenadas
        if ($datos['Latitud'] < -90 || $datos['Latitud'] > 90) {
            $errores[] = 'Latitud fuera de rango válido (-90 a 90)';
        }
        
        if ($datos['Longitud'] < -180 || $datos['Longitud'] > 180) {
            $errores[] = 'Longitud fuera de rango válido (-180 a 180)';
        }
        
        // Validar velocidad realista
        if (isset($datos['Velocidad']) && $datos['Velocidad'] > self::VELOCIDAD_MAXIMA_REALISTA) {
            $errores[] = "Velocidad no realista: {$datos['Velocidad']} km/h (máx: " . self::VELOCIDAD_MAXIMA_REALISTA . ")";
        }
        
        // Validar timestamp no futuro
        if (isset($datos['FechaHora'])) {
            $fecha = Carbon::parse($datos['FechaHora']);
            if ($fecha->isFuture()) {
                $errores[] = 'Timestamp no puede ser futuro';
            }
        }
        
        // Validar accuracy si existe
        if (isset($datos['Accuracy']) && $datos['Accuracy'] > self::ACCURACY_MAXIMA) {
            $errores[] = "Precisión muy baja: {$datos['Accuracy']}m (máx: " . self::ACCURACY_MAXIMA . "m)";
        }
        
        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }
    
    /**
     * Detectar saltos imposibles en la ubicación
     */
    public function detectarSaltoImposible(int $dispositivoId, float $latitud, float $longitud, Carbon $timestamp): bool
    {
        $ultimaUbicacion = Ubicacion::where('DispositivoID', $dispositivoId)
            ->orderBy('FechaHora', 'desc')
            ->first();
        
        if (!$ultimaUbicacion) {
            return false; // Primera ubicación, no hay con qué comparar
        }
        
        // Calcular distancia
        $distancia = $this->calcularDistanciaHaversine(
            $ultimaUbicacion->Latitud,
            $ultimaUbicacion->Longitud,
            $latitud,
            $longitud
        );
        
        // Calcular tiempo transcurrido en segundos
        $segundos = $ultimaUbicacion->FechaHora->diffInSeconds($timestamp);
        
        if ($segundos == 0) {
            return false;
        }
        
        // Calcular velocidad necesaria para ese salto
        $velocidadNecesaria = ($distancia / $segundos) * 3.6; // m/s a km/h
        
        // Si la velocidad necesaria es mayor a 200 km/h, es un salto imposible
        return $velocidadNecesaria > self::VELOCIDAD_MAXIMA_REALISTA;
    }
    
    /**
     * Filtrar ruido de datos GPS usando algoritmo de suavizado
     * Promedio móvil simple de las últimas N ubicaciones
     */
    public function suavizarUbicaciones(int $dispositivoId, int $ventana = 5): array
    {
        $ubicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->orderBy('FechaHora', 'desc')
            ->limit($ventana * 2)
            ->get()
            ->reverse();
        
        if ($ubicaciones->count() < $ventana) {
            return [];
        }
        
        $suavizadas = [];
        
        for ($i = $ventana - 1; $i < $ubicaciones->count(); $i++) {
            $grupo = $ubicaciones->slice($i - $ventana + 1, $ventana);
            
            $latPromedio = $grupo->avg('Latitud');
            $lngPromedio = $grupo->avg('Longitud');
            $velPromedio = $grupo->avg('Velocidad');
            
            $suavizadas[] = [
                'latitud' => round($latPromedio, 8),
                'longitud' => round($lngPromedio, 8),
                'velocidad' => round($velPromedio, 2),
                'timestamp' => $ubicaciones[$i]->FechaHora->format('Y-m-d H:i:s')
            ];
        }
        
        return $suavizadas;
    }
    
    /**
     * Limpiar datos GPS inválidos
     */
    public function limpiarDatosInvalidos(int $dispositivoId = null): array
    {
        $query = Ubicacion::query();
        
        if ($dispositivoId) {
            $query->where('DispositivoID', $dispositivoId);
        }
        
        $ubicaciones = $query->orderBy('FechaHora', 'asc')->get();
        
        $eliminadas = 0;
        $marcadasInvalidas = [];
        
        foreach ($ubicaciones as $ubicacion) {
            $validacion = $this->esUbicacionValida([
                'Latitud' => $ubicacion->Latitud,
                'Longitud' => $ubicacion->Longitud,
                'Velocidad' => $ubicacion->Velocidad,
                'FechaHora' => $ubicacion->FechaHora
            ]);
            
            if (!$validacion['valido']) {
                $marcadasInvalidas[] = [
                    'ubicacion_id' => $ubicacion->UbicacionID,
                    'errores' => $validacion['errores']
                ];
                $eliminadas++;
            }
        }
        
        return [
            'total_revisadas' => $ubicaciones->count(),
            'total_invalidas' => $eliminadas,
            'ubicaciones_invalidas' => $marcadasInvalidas
        ];
    }
    
    /**
     * Calcular ruta optimizada eliminando puntos redundantes
     * Usa algoritmo Douglas-Peucker simplificado
     */
    public function optimizarRuta(int $dispositivoId, Carbon $inicio, Carbon $fin, float $tolerancia = 0.0001): array
    {
        $ubicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->whereBetween('FechaHora', [$inicio, $fin])
            ->orderBy('FechaHora', 'asc')
            ->get();
        
        if ($ubicaciones->count() < 3) {
            return $ubicaciones->toArray();
        }
        
        $puntos = $ubicaciones->map(function($u) {
            return [
                'lat' => $u->Latitud,
                'lng' => $u->Longitud,
                'timestamp' => $u->FechaHora->format('Y-m-d H:i:s')
            ];
        })->toArray();
        
        $optimizados = $this->douglasPeucker($puntos, $tolerancia);
        
        return [
            'original_count' => count($puntos),
            'optimized_count' => count($optimizados),
            'reduccion_porcentaje' => round((1 - count($optimizados) / count($puntos)) * 100, 2),
            'puntos' => $optimizados
        ];
    }
    
    /**
     * Algoritmo Douglas-Peucker para simplificar rutas
     */
    private function douglasPeucker(array $puntos, float $tolerancia): array
    {
        if (count($puntos) < 3) {
            return $puntos;
        }
        
        $distanciaMax = 0;
        $indiceMax = 0;
        $fin = count($puntos) - 1;
        
        for ($i = 1; $i < $fin; $i++) {
            $distancia = $this->distanciaPuntoLinea(
                $puntos[$i],
                $puntos[0],
                $puntos[$fin]
            );
            
            if ($distancia > $distanciaMax) {
                $distanciaMax = $distancia;
                $indiceMax = $i;
            }
        }
        
        if ($distanciaMax > $tolerancia) {
            $izquierda = $this->douglasPeucker(array_slice($puntos, 0, $indiceMax + 1), $tolerancia);
            $derecha = $this->douglasPeucker(array_slice($puntos, $indiceMax), $tolerancia);
            
            return array_merge(
                array_slice($izquierda, 0, -1),
                $derecha
            );
        }
        
        return [$puntos[0], $puntos[$fin]];
    }
    
    /**
     * Calcular distancia perpendicular de un punto a una línea
     */
    private function distanciaPuntoLinea(array $punto, array $lineaInicio, array $lineaFin): float
    {
        $x0 = $punto['lat'];
        $y0 = $punto['lng'];
        $x1 = $lineaInicio['lat'];
        $y1 = $lineaInicio['lng'];
        $x2 = $lineaFin['lat'];
        $y2 = $lineaFin['lng'];
        
        $numerador = abs(($y2 - $y1) * $x0 - ($x2 - $x1) * $y0 + $x2 * $y1 - $y2 * $x1);
        $denominador = sqrt(pow($y2 - $y1, 2) + pow($x2 - $x1, 2));
        
        return $denominador != 0 ? $numerador / $denominador : 0;
    }
    
    /**
     * Fórmula Haversine
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
        
        return $radioTierra * $c;
    }
    
    /**
     * Obtener estadísticas de calidad de datos GPS
     */
    public function obtenerEstadisticasCalidad(int $dispositivoId, Carbon $inicio, Carbon $fin): array
    {
        $ubicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->whereBetween('FechaHora', [$inicio, $fin])
            ->get();
        
        $total = $ubicaciones->count();
        $invalidas = 0;
        $saltosImposibles = 0;
        
        foreach ($ubicaciones as $ubicacion) {
            $validacion = $this->esUbicacionValida([
                'Latitud' => $ubicacion->Latitud,
                'Longitud' => $ubicacion->Longitud,
                'Velocidad' => $ubicacion->Velocidad,
                'FechaHora' => $ubicacion->FechaHora
            ]);
            
            if (!$validacion['valido']) {
                $invalidas++;
            }
        }
        
        return [
            'total_puntos' => $total,
            'puntos_validos' => $total - $invalidas,
            'puntos_invalidos' => $invalidas,
            'porcentaje_calidad' => $total > 0 ? round((($total - $invalidas) / $total) * 100, 2) : 0
        ];
    }
}
