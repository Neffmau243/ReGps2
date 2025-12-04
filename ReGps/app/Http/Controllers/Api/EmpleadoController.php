<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmpleadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $empleados = Empleado::with('usuario', 'dispositivos')->get();
        return response()->json($empleados);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'UsuarioID' => 'required|exists:usuarios,UsuarioID',
            'Nombre' => 'required|string|max:100',
            'Apellido' => 'required|string|max:100',
            'Telefono' => 'nullable|string|max:20',
            'Direccion' => 'nullable|string',
            'Estado' => 'in:Activo,Inactivo'
        ]);

        $empleado = Empleado::create($validated);
        return response()->json($empleado, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $empleado = Empleado::with('usuario', 'dispositivos')->findOrFail($id);
        return response()->json($empleado);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $empleado = Empleado::findOrFail($id);
        
        $validated = $request->validate([
            'UsuarioID' => 'exists:usuarios,UsuarioID',
            'Nombre' => 'string|max:100',
            'Apellido' => 'string|max:100',
            'Telefono' => 'nullable|string|max:20',
            'Direccion' => 'nullable|string',
            'Estado' => 'in:Activo,Inactivo'
        ]);

        $empleado->update($validated);
        return response()->json($empleado);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $empleado = Empleado::findOrFail($id);
        $empleado->delete();
        return response()->json(['message' => 'Empleado eliminado correctamente']);
    }
}
