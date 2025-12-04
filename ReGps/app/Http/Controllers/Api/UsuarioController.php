<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $usuarios = Usuario::with('empleado')->get();
        return response()->json($usuarios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'Nombre' => 'required|string|max:100',
            'Email' => 'required|email|unique:usuarios,Email',
            'Contraseña' => 'required|string|min:6',
            'Rol' => 'required|in:Administrador,Empleado',
            'Estado' => 'in:Activo,Inactivo'
        ]);

        // NO hashear aquí - el mutador del modelo lo hace automáticamente
        $usuario = Usuario::create($validated);
        
        // Crear automáticamente el registro en empleados para TODOS los usuarios
        // Esto permite que cualquier usuario (Admin o Empleado) pueda tener dispositivos asignados
        \App\Models\Empleado::create([
            'UsuarioID' => $usuario->UsuarioID,
            'Nombre' => $validated['Nombre'],
            'Apellido' => '', // Vacío por defecto, se puede editar después
            'Estado' => 'Activo'
        ]);
        
        return response()->json($usuario->load('empleado'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $usuario = Usuario::with('empleado')->findOrFail($id);
        return response()->json($usuario);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);
        
        $validated = $request->validate([
            'Nombre' => 'string|max:100',
            'Email' => 'email|unique:usuarios,Email,' . $id . ',UsuarioID',
            'Contraseña' => 'string|min:6',
            'Rol' => 'in:Administrador,Empleado',
            'Estado' => 'in:Activo,Inactivo'
        ]);

        // NO hashear aquí - el mutador del modelo lo hace automáticamente
        // Si la contraseña está presente en $validated, el mutador la hasheará
        $usuario->update($validated);
        return response()->json($usuario);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);
        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
}
