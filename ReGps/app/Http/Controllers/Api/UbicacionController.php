<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ubicacion;
use App\Events\UbicacionActualizada;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UbicacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $ubicaciones = Ubicacion::with('dispositivo.empleado')->get();
        return response()->json($ubicaciones);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'DispositivoID' => 'required|exists:dispositivos,DispositivoID',
            'Latitud' => 'required|numeric|between:-90,90',
            'Longitud' => 'required|numeric|between:-180,180',
            'Velocidad' => 'nullable|numeric|min:0|max:200',
            'Direccion' => 'nullable|string',
            'FechaHora' => 'required|date|before_or_equal:now'
        ]);

        $ubicacion = Ubicacion::create($validated);
        
        // Verificar geofencing automáticamente
        $this->verificarGeofencing($ubicacion);
        
        // Transmitir la actualización por WebSocket
        broadcast(new UbicacionActualizada($ubicacion))->toOthers();
        
        return response()->json($ubicacion, 201);
    }
    
    /**
     * Verificar si la ubicación está en alguna zona y registrar eventos
     */
    private function verificarGeofencing($ubicacion)
    {
        $dispositivo = \App\Models\Dispositivo::with('empleado')->find($ubicacion->DispositivoID);
        
        if (!$dispositivo || !$dispositivo->empleado) {
            return;
        }
        
        $zonasActivas = \App\Models\Zona::where('Estado', 'Activo')->get();
        
        foreach ($zonasActivas as $zona) {
            $estaEnZona = $zona->contienePunto($ubicacion->Latitud, $ubicacion->Longitud);
            
            // Buscar última entrada sin salida
            $ultimaEntrada = \App\Models\HistorialZona::where('ZonaID', $zona->ZonaID)
                ->where('EmpleadoID', $dispositivo->EmpleadoID)
                ->where('TipoEvento', 'Entrada')
                ->whereNull('TiempoPermanencia')
                ->latest('FechaHoraEvento')
                ->first();
            
            if ($estaEnZona && !$ultimaEntrada) {
                // Registrar entrada
                \App\Models\HistorialZona::create([
                    'ZonaID' => $zona->ZonaID,
                    'EmpleadoID' => $dispositivo->EmpleadoID,
                    'DispositivoID' => $dispositivo->DispositivoID,
                    'TipoEvento' => 'Entrada',
                    'FechaHoraEvento' => $ubicacion->FechaHora,
                    'Latitud' => $ubicacion->Latitud,
                    'Longitud' => $ubicacion->Longitud,
                    'AlertaGenerada' => $zona->TipoZona === 'Zona Restringida'
                ]);
                
                // Generar alerta si es zona restringida
                if ($zona->TipoZona === 'Zona Restringida') {
                    \App\Models\Alerta::create([
                        'DispositivoID' => $dispositivo->DispositivoID,
                        'TipoAlerta' => 'Zona',
                        'Descripcion' => "Entrada a zona restringida: {$zona->Nombre}",
                        'FechaHora' => $ubicacion->FechaHora,
                        'Estado' => 'Pendiente'
                    ]);
                }
            } elseif (!$estaEnZona && $ultimaEntrada) {
                // Registrar salida y calcular tiempo de permanencia
                $tiempoPermanencia = $ubicacion->FechaHora->diffInMinutes($ultimaEntrada->FechaHoraEvento);
                
                $ultimaEntrada->update(['TiempoPermanencia' => $tiempoPermanencia]);
                
                \App\Models\HistorialZona::create([
                    'ZonaID' => $zona->ZonaID,
                    'EmpleadoID' => $dispositivo->EmpleadoID,
                    'DispositivoID' => $dispositivo->DispositivoID,
                    'TipoEvento' => 'Salida',
                    'FechaHoraEvento' => $ubicacion->FechaHora,
                    'Latitud' => $ubicacion->Latitud,
                    'Longitud' => $ubicacion->Longitud,
                    'TiempoPermanencia' => $tiempoPermanencia,
                    'AlertaGenerada' => $zona->TipoZona === 'Zona Permitida'
                ]);
                
                // Generar alerta si sale de zona permitida
                if ($zona->TipoZona === 'Zona Permitida') {
                    \App\Models\Alerta::create([
                        'DispositivoID' => $dispositivo->DispositivoID,
                        'TipoAlerta' => 'Zona',
                        'Descripcion' => "Salida de zona permitida: {$zona->Nombre}",
                        'FechaHora' => $ubicacion->FechaHora,
                        'Estado' => 'Pendiente'
                    ]);
                }
            }
        }
        
        // Verificar exceso de velocidad
        if ($ubicacion->Velocidad && $ubicacion->Velocidad > 80) {
            \App\Models\Alerta::create([
                'DispositivoID' => $dispositivo->DispositivoID,
                'TipoAlerta' => 'Velocidad',
                'Descripcion' => "Exceso de velocidad: {$ubicacion->Velocidad} km/h",
                'FechaHora' => $ubicacion->FechaHora,
                'Estado' => 'Pendiente'
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $ubicacion = Ubicacion::with('dispositivo.empleado')->findOrFail($id);
        return response()->json($ubicacion);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $ubicacion = Ubicacion::findOrFail($id);
        
        $validated = $request->validate([
            'DispositivoID' => 'exists:dispositivos,DispositivoID',
            'Latitud' => 'numeric|between:-90,90',
            'Longitud' => 'numeric|between:-180,180',
            'Velocidad' => 'nullable|numeric|min:0',
            'Direccion' => 'nullable|string',
            'FechaHora' => 'date'
        ]);

        $ubicacion->update($validated);
        return response()->json($ubicacion);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $ubicacion = Ubicacion::findOrFail($id);
        $ubicacion->delete();
        return response()->json(['message' => 'Ubicación eliminada correctamente']);
    }
    
    /**
     * Get current locations of all devices (last location per device)
     */
    public function getCurrentLocations(): JsonResponse
    {
        // Obtener la última ubicación de cada dispositivo
        $ubicaciones = \App\Models\Ubicacion::select('ubicaciones.*')
            ->join(\DB::raw('(SELECT DispositivoID, MAX(FechaHora) as MaxFecha FROM ubicaciones GROUP BY DispositivoID) as latest'), function($join) {
                $join->on('ubicaciones.DispositivoID', '=', 'latest.DispositivoID')
                     ->on('ubicaciones.FechaHora', '=', 'latest.MaxFecha');
            })
            ->with(['dispositivo.empleado.usuario'])
            ->get();
        
        // Formatear respuesta para el frontend
        $locations = $ubicaciones->map(function($ubicacion) {
            $dispositivo = $ubicacion->dispositivo;
            $empleado = $dispositivo->empleado ?? null;
            $usuario = $empleado->usuario ?? null;
            
            $minutesAgo = $ubicacion->FechaHora->diffInMinutes(now());
            
            return [
                'device_id' => $dispositivo->DispositivoID,
                'device_name' => $dispositivo->Modelo ?? 'Dispositivo ' . $dispositivo->DispositivoID,
                'device_serial' => $dispositivo->IMEI,
                'user_id' => $usuario->UsuarioID ?? null,
                'user_name' => $usuario->Nombre ?? 'Usuario Desconocido',
                'latitude' => (float) $ubicacion->Latitud,
                'longitude' => (float) $ubicacion->Longitud,
                'accuracy' => 10.0, // Valor por defecto
                'timestamp' => $ubicacion->FechaHora->toIso8601String(),
                'minutes_ago' => $minutesAgo,
            ];
        });
        
        return response()->json($locations);
    }
    
    /**
     * Get location history with filters
     */
    public function getHistory(Request $request): JsonResponse
    {
        $query = \App\Models\Ubicacion::with(['dispositivo.empleado.usuario']);
        
        // Filtros
        if ($request->has('device_id')) {
            $query->where('DispositivoID', $request->device_id);
        }
        
        if ($request->has('start_date')) {
            $query->where('FechaHora', '>=', $request->start_date . ' 00:00:00');
        }
        
        if ($request->has('end_date')) {
            $query->where('FechaHora', '<=', $request->end_date . ' 23:59:59');
        }
        
        $ubicaciones = $query->orderBy('FechaHora', 'desc')
            ->limit($request->input('limit', 1000))
            ->get();
        
        if ($ubicaciones->isEmpty()) {
            return response()->json([
                'device' => null,
                'locations' => [],
                'statistics' => [
                    'total_points' => 0,
                    'distance_km' => 0,
                    'duration_minutes' => 0,
                ]
            ]);
        }
        
        // Información del dispositivo
        $firstUbicacion = $ubicaciones->first();
        $dispositivo = $firstUbicacion->dispositivo;
        $empleado = $dispositivo->empleado ?? null;
        $usuario = $empleado->usuario ?? null;
        
        // Formatear ubicaciones
        $locations = $ubicaciones->map(function($ubicacion) {
            return [
                'latitude' => (float) $ubicacion->Latitud,
                'longitude' => (float) $ubicacion->Longitud,
                'accuracy' => 10.0,
                'timestamp' => $ubicacion->FechaHora->toIso8601String(),
            ];
        });
        
        // Calcular estadísticas
        $totalPoints = $ubicaciones->count();
        $distanceKm = 0;
        
        // Calcular distancia total
        for ($i = 0; $i < $totalPoints - 1; $i++) {
            $loc1 = $ubicaciones[$i];
            $loc2 = $ubicaciones[$i + 1];
            $distanceKm += $this->calculateDistance(
                $loc1->Latitud,
                $loc1->Longitud,
                $loc2->Latitud,
                $loc2->Longitud
            );
        }
        
        // Calcular duración
        $durationMinutes = 0;
        if ($totalPoints > 1) {
            $firstTime = $ubicaciones->last()->FechaHora;
            $lastTime = $ubicaciones->first()->FechaHora;
            $durationMinutes = $firstTime->diffInMinutes($lastTime);
        }
        
        return response()->json([
            'device' => [
                'id' => $dispositivo->DispositivoID,
                'name' => $dispositivo->Modelo ?? 'Dispositivo ' . $dispositivo->DispositivoID,
                'user_name' => $usuario->Nombre ?? 'Usuario Desconocido',
            ],
            'locations' => $locations,
            'statistics' => [
                'total_points' => $totalPoints,
                'distance_km' => round($distanceKm, 2),
                'duration_minutes' => $durationMinutes,
            ]
        ]);
    }
    
    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
}
