<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispositivo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DispositivoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $dispositivos = Dispositivo::with('empleado')->get();
        return response()->json($dispositivos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'EmpleadoID' => 'nullable|exists:empleados,EmpleadoID',
            'IMEI' => 'required|string|unique:dispositivos,IMEI|min:10|max:20',
            'Modelo' => 'required|string|max:100',
            'Marca' => 'nullable|string|max:100',
            'Estado' => 'nullable|in:Activo,Inactivo,Mantenimiento',
            'FechaAsignacion' => 'nullable|date'
        ]);

        // Valores por defecto
        $validated['Estado'] = $validated['Estado'] ?? 'Activo';
        $validated['FechaAsignacion'] = $validated['FechaAsignacion'] ?? now();

        $dispositivo = Dispositivo::create($validated);
        return response()->json($dispositivo, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $dispositivo = Dispositivo::with('empleado', 'ubicaciones', 'alertas')->findOrFail($id);
        return response()->json($dispositivo);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $dispositivo = Dispositivo::findOrFail($id);
        
        $validated = $request->validate([
            'EmpleadoID' => 'nullable|exists:empleados,EmpleadoID',
            'IMEI' => 'string|unique:dispositivos,IMEI,' . $id . ',DispositivoID',
            'Modelo' => 'nullable|string|max:100',
            'Marca' => 'nullable|string|max:100',
            'Estado' => 'in:Activo,Inactivo,Mantenimiento',
            'FechaAsignacion' => 'nullable|date'
        ]);

        $dispositivo->update($validated);
        return response()->json($dispositivo);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $dispositivo = Dispositivo::findOrFail($id);
        $dispositivo->delete();
        return response()->json(['message' => 'Dispositivo eliminado correctamente']);
    }

    /**
     * Obtener los dispositivos asignados al empleado actual.
     */
    public function misDispositivos(Request $request): JsonResponse
    {
        $usuario = $request->user();
        
        // Si es administrador, puede ver todos
        if ($usuario->Rol === 'Administrador') {
            $dispositivos = Dispositivo::with('empleado')->get();
            return response()->json($dispositivos);
        }
        
        // Si es empleado, buscar su EmpleadoID
        $empleado = \App\Models\Empleado::where('UsuarioID', $usuario->UsuarioID)->first();
        
        if (!$empleado) {
            return response()->json(['message' => 'No se encontró el empleado asociado'], 404);
        }
        
        // Retornar solo los dispositivos asignados a este empleado
        $dispositivos = Dispositivo::where('EmpleadoID', $empleado->EmpleadoID)
            ->with('empleado')
            ->get();
        
        return response()->json($dispositivos);
    }
}
