<?php

namespace App\Services;

use App\Models\Ubicacion;
use App\Models\Dispositivo;
use Carbon\Carbon;

class MovementDetectionService
{
    const VELOCIDAD_MINIMA_MOVIMIENTO = 5; // km/h
    const MINUTOS_INACTIVIDAD = 15;
    const MINUTOS_FUERA_RANGO = 30;
    
    /**
     * Detectar estado actual del dispositivo
     */
    public function detectarEstado(int $dispositivoId): array
    {
        $ultimaUbicacion = Ubicacion::where('DispositivoID', $dispositivoId)
            ->orderBy('FechaHora', 'desc')
            ->first();
        
        if (!$ultimaUbicacion) {
            return [
                'estado' => 'sin_datos',
                'descripcion' => 'No hay ubicaciones registradas',
                'color' => 'gray',
                'icono' => 'question'
            ];
        }
        
        $minutosDesdeUltimaUbicacion = Carbon::now()->diffInMinutes($ultimaUbicacion->FechaHora);
        
        // Fuera de rango (sin conexión)
        if ($minutosDesdeUltimaUbicacion > self::MINUTOS_FUERA_RANGO) {
            return [
                'estado' => 'fuera_rango',
                'descripcion' => "Sin conexión desde hace {$minutosDesdeUltimaUbicacion} minutos",
                'color' => 'red',
                'icono' => 'signal-slash',
                'ultima_ubicacion' => $ultimaUbicacion->FechaHora->format('Y-m-d H:i:s'),
                'minutos_sin_conexion' => $minutosDesdeUltimaUbicacion
            ];
        }
        
        // Inactivo (conectado pero sin movimiento prolongado)
        if ($minutosDesdeUltimaUbicacion > self::MINUTOS_INACTIVIDAD) {
            return [
                'estado' => 'inactivo',
                'descripcion' => "Inactivo desde hace {$minutosDesdeUltimaUbicacion} minutos",
                'color' => 'orange',
                'icono' => 'pause',
                'ultima_ubicacion' => $ultimaUbicacion->FechaHora->format('Y-m-d H:i:s'),
                'minutos_inactivo' => $minutosDesdeUltimaUbicacion
            ];
        }
        
        // En movimiento o detenido (basado en velocidad)
        if ($ultimaUbicacion->Velocidad >= self::VELOCIDAD_MINIMA_MOVIMIENTO) {
            return [
                'estado' => 'en_movimiento',
                'descripcion' => "En movimiento a {$ultimaUbicacion->Velocidad} km/h",
                'color' => 'green',
                'icono' => 'car',
                'velocidad' => $ultimaUbicacion->Velocidad,
                'ultima_actualizacion' => $ultimaUbicacion->FechaHora->format('Y-m-d H:i:s')
            ];
        }
        
        return [
            'estado' => 'detenido',
            'descripcion' => 'Detenido',
            'color' => 'yellow',
            'icono' => 'stop',
            'velocidad' => 0,
            'ultima_actualizacion' => $ultimaUbicacion->FechaHora->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Detectar dispositivos inactivos
     */
    public function detectarDispositivosInactivos(): array
    {
        $fechaLimite = Carbon::now()->subMinutes(self::MINUTOS_INACTIVIDAD);
        
        $dispositivos = Dispositivo::where('Estado', 'Activo')
            ->with(['empleado', 'ubicaciones' => function($query) {
                $query->orderBy('FechaHora', 'desc')->limit(1);
            }])
            ->get();
        
        $inactivos = [];
        
        foreach ($dispositivos as $dispositivo) {
            $ultimaUbicacion = $dispositivo->ubicaciones->first();
            
            if (!$ultimaUbicacion || $ultimaUbicacion->FechaHora < $fechaLimite) {
                $inactivos[] = [
                    'dispositivo_id' => $dispositivo->DispositivoID,
                    'imei' => $dispositivo->IMEI,
                    'empleado' => $dispositivo->empleado ? $dispositivo->empleado->Nombre . ' ' . $dispositivo->empleado->Apellido : 'Sin asignar',
                    'ultima_ubicacion' => $ultimaUbicacion ? $ultimaUbicacion->FechaHora->format('Y-m-d H:i:s') : 'Nunca',
                    'minutos_inactivo' => $ultimaUbicacion ? Carbon::now()->diffInMinutes($ultimaUbicacion->FechaHora) : null
                ];
            }
        }
        
        return $inactivos;
    }
    
    /**
     * Detectar paradas no autorizadas
     * Una parada es no autorizada si está fuera de zonas permitidas y dura más de X minutos
     */
    public function detectarParadasNoAutorizadas(int $dispositivoId, int $minutosMinimos = 30): array
    {
        $ubicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->where('Velocidad', '<', self::VELOCIDAD_MINIMA_MOVIMIENTO)
            ->orderBy('FechaHora', 'desc')
            ->limit(100)
            ->get();
        
        $paradas = [];
        $paradaActual = null;
        
        foreach ($ubicaciones as $ubicacion) {
            if (!$paradaActual) {
                $paradaActual = [
                    'inicio' => $ubicacion->FechaHora,
                    'ubicacion' => $ubicacion
                ];
            } else {
                $diferencia = $paradaActual['inicio']->diffInMinutes($ubicacion->FechaHora);
                
                if ($diferencia >= $minutosMinimos) {
                    // Verificar si está en zona permitida
                    $enZonaPermitida = \App\Models\Zona::where('Estado', 'Activo')
                        ->where('TipoZona', 'Zona Permitida')
                        ->get()
                        ->contains(function($zona) use ($ubicacion) {
                            return $zona->contienePunto($ubicacion->Latitud, $ubicacion->Longitud);
                        });
                    
                    if (!$enZonaPermitida) {
                        $paradas[] = [
                            'inicio' => $paradaActual['inicio']->format('Y-m-d H:i:s'),
                            'duracion_minutos' => $diferencia,
                            'latitud' => $ubicacion->Latitud,
                            'longitud' => $ubicacion->Longitud,
                            'direccion' => $ubicacion->Direccion
                        ];
                    }
                    
                    $paradaActual = null;
                }
            }
        }
        
        return $paradas;
    }
    
    /**
     * Calcular tiempo total en movimiento vs detenido
     */
    public function calcularTiemposMovimiento(int $dispositivoId, Carbon $inicio, Carbon $fin): array
    {
        $ubicaciones = Ubicacion::where('DispositivoID', $dispositivoId)
            ->whereBetween('FechaHora', [$inicio, $fin])
            ->orderBy('FechaHora', 'asc')
            ->get();
        
        $tiempoMovimiento = 0;
        $tiempoDetenido = 0;
        
        for ($i = 0; $i < $ubicaciones->count() - 1; $i++) {
            $diferencia = $ubicaciones[$i]->FechaHora->diffInMinutes($ubicaciones[$i + 1]->FechaHora);
            
            if ($ubicaciones[$i]->Velocidad >= self::VELOCIDAD_MINIMA_MOVIMIENTO) {
                $tiempoMovimiento += $diferencia;
            } else {
                $tiempoDetenido += $diferencia;
            }
        }
        
        $total = $tiempoMovimiento + $tiempoDetenido;
        
        return [
            'tiempo_movimiento_minutos' => $tiempoMovimiento,
            'tiempo_detenido_minutos' => $tiempoDetenido,
            'porcentaje_movimiento' => $total > 0 ? round(($tiempoMovimiento / $total) * 100, 2) : 0,
            'porcentaje_detenido' => $total > 0 ? round(($tiempoDetenido / $total) * 100, 2) : 0
        ];
    }
    
    /**
     * Obtener resumen de estados de todos los dispositivos
     */
    public function obtenerResumenEstados(): array
    {
        $dispositivos = Dispositivo::where('Estado', 'Activo')->get();
        
        $resumen = [
            'en_movimiento' => 0,
            'detenido' => 0,
            'inactivo' => 0,
            'fuera_rango' => 0,
            'sin_datos' => 0,
            'total' => $dispositivos->count()
        ];
        
        foreach ($dispositivos as $dispositivo) {
            $estado = $this->detectarEstado($dispositivo->DispositivoID);
            $resumen[$estado['estado']]++;
        }
        
        return $resumen;
    }
}
