<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alerta;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $alertas = Alerta::with('dispositivo.empleado', 'asignado')
            ->orderBy('Prioridad', 'desc')
            ->orderBy('FechaHora', 'desc')
            ->get();
        return response()->json($alertas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'DispositivoID' => 'required|exists:dispositivos,DispositivoID',
            'TipoAlerta' => 'required|in:Velocidad,Zona,Batería,Desconexión,Emergencia',
            'Prioridad' => 'in:Baja,Media,Alta,Crítica',
            'Descripcion' => 'nullable|string',
            'FechaHora' => 'required|date',
            'Estado' => 'in:Pendiente,Revisada,Resuelta',
            'AsignadoA' => 'nullable|exists:usuarios,UsuarioID',
            'Notas' => 'nullable|string'
        ]);

        $alerta = Alerta::create($validated);
        return response()->json($alerta, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $alerta = Alerta::with('dispositivo.empleado')->findOrFail($id);
        return response()->json($alerta);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $alerta = Alerta::findOrFail($id);
        
        $validated = $request->validate([
            'DispositivoID' => 'exists:dispositivos,DispositivoID',
            'TipoAlerta' => 'in:Velocidad,Zona,Batería,Desconexión,Emergencia',
            'Prioridad' => 'in:Baja,Media,Alta,Crítica',
            'Descripcion' => 'nullable|string',
            'FechaHora' => 'date',
            'Estado' => 'in:Pendiente,Revisada,Resuelta',
            'AsignadoA' => 'nullable|exists:usuarios,UsuarioID',
            'Notas' => 'nullable|string'
        ]);

        $alerta->update($validated);
        return response()->json($alerta);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $alerta = Alerta::findOrFail($id);
        $alerta->delete();
        return response()->json(['message' => 'Alerta eliminada correctamente']);
    }
}
