<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Models\HistorialZona;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ZonaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $zonas = Zona::all();
        return response()->json($zonas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'Nombre' => 'required|string|max:100',
            'TipoZona' => 'required|in:Checkpoint,Zona Permitida,Zona Restringida',
            'TipoGeometria' => 'required|in:Circulo,Poligono',
            'Latitud' => 'required|numeric|between:-90,90',
            'Longitud' => 'required|numeric|between:-180,180',
            'Radio' => 'nullable|integer|min:1',
            'Coordenadas' => 'nullable|array',
            'Coordenadas.*.lat' => 'required_with:Coordenadas|numeric|between:-90,90',
            'Coordenadas.*.lng' => 'required_with:Coordenadas|numeric|between:-180,180',
            'HorarioInicio' => 'nullable|date_format:H:i',
            'HorarioFin' => 'nullable|date_format:H:i',
            'Descripcion' => 'nullable|string',
            'Estado' => 'in:Activo,Inactivo'
        ]);

        $zona = Zona::create($validated);
        return response()->json($zona, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $zona = Zona::with('historial')->findOrFail($id);
        return response()->json($zona);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $zona = Zona::findOrFail($id);
        
        $validated = $request->validate([
            'Nombre' => 'string|max:100',
            'TipoZona' => 'in:Checkpoint,Zona Permitida,Zona Restringida',
            'TipoGeometria' => 'in:Circulo,Poligono',
            'Latitud' => 'numeric|between:-90,90',
            'Longitud' => 'numeric|between:-180,180',
            'Radio' => 'nullable|integer|min:1',
            'Coordenadas' => 'nullable|array',
            'Coordenadas.*.lat' => 'required_with:Coordenadas|numeric|between:-90,90',
            'Coordenadas.*.lng' => 'required_with:Coordenadas|numeric|between:-180,180',
            'HorarioInicio' => 'nullable|date_format:H:i',
            'HorarioFin' => 'nullable|date_format:H:i',
            'Descripcion' => 'nullable|string',
            'Estado' => 'in:Activo,Inactivo'
        ]);

        $zona->update($validated);
        return response()->json($zona);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $zona = Zona::findOrFail($id);
        $zona->delete();
        return response()->json(['message' => 'Zona eliminada correctamente']);
    }
    
    /**
     * Verificar si un dispositivo está en una zona
     */
    public function verificarUbicacion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'DispositivoID' => 'required|exists:dispositivos,DispositivoID',
            'Latitud' => 'required|numeric|between:-90,90',
            'Longitud' => 'required|numeric|between:-180,180'
        ]);
        
        $zonasActivas = Zona::where('Estado', 'Activo')->get();
        $zonasEncontradas = [];
        
        foreach ($zonasActivas as $zona) {
            if ($zona->contienePunto($validated['Latitud'], $validated['Longitud'])) {
                $zonasEncontradas[] = $zona;
            }
        }
        
        return response()->json([
            'en_zona' => count($zonasEncontradas) > 0,
            'zonas' => $zonasEncontradas
        ]);
    }
    
    /**
     * Obtener historial de una zona
     */
    public function historial(string $id): JsonResponse
    {
        $historial = HistorialZona::where('ZonaID', $id)
            ->with(['empleado', 'dispositivo'])
            ->orderBy('FechaHoraEvento', 'desc')
            ->get();
            
        return response()->json($historial);
    }
}
