<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login y generar token
     */
    public function login(Request $request)
    {
        $request->validate([
            'Email' => 'required|email',
            'Contraseña' => 'required'
        ]);

        $usuario = Usuario::where('Email', $request->Email)->first();
        
        // DEBUG: Log para verificación
        \Log::info('=== LOGIN ATTEMPT ===');
        \Log::info('Email: ' . $request->Email);
        \Log::info('Usuario encontrado: ' . ($usuario ? 'SI' : 'NO'));
        
        if ($usuario) {
            \Log::info('Hash en BD: ' . substr($usuario->Contraseña, 0, 30) . '...');
            \Log::info('Contraseña ingresada: ' . $request->Contraseña);
            \Log::info('Hash check result: ' . (Hash::check($request->Contraseña, $usuario->Contraseña) ? 'PASS' : 'FAIL'));
        }

        if (!$usuario || !Hash::check($request->Contraseña, $usuario->Contraseña)) {
            \Log::warning('Login fallido - credenciales incorrectas');
            throw ValidationException::withMessages([
                'Email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        if ($usuario->Estado !== 'Activo') {
            \Log::warning('Login fallido - usuario inactivo');
            return response()->json([
                'message' => 'Usuario inactivo. Contacte al administrador.'
            ], 403);
        }

        // Actualizar último login
        $usuario->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        // Crear token
        $token = $usuario->createToken('auth-token', [
            $usuario->Rol === 'Administrador' ? '*' : 'empleado'
        ])->plainTextToken;
        
        \Log::info('Login exitoso para: ' . $usuario->Email);

        return response()->json([
            'message' => 'Login exitoso',
            'token' => $token,
            'usuario' => [
                'UsuarioID' => $usuario->UsuarioID,
                'Nombre' => $usuario->Nombre,
                'Email' => $usuario->Email,
                'Rol' => $usuario->Rol,
                'Estado' => $usuario->Estado
            ]
        ], 200);
    }

    /**
     * Logout y revocar token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout exitoso'
        ], 200);
    }

    /**
     * Obtener usuario autenticado
     */
    public function me(Request $request)
    {
        $usuario = $request->user();
        
        return response()->json([
            'usuario' => [
                'UsuarioID' => $usuario->UsuarioID,
                'Nombre' => $usuario->Nombre,
                'Email' => $usuario->Email,
                'Rol' => $usuario->Rol,
                'Estado' => $usuario->Estado,
                'last_login_at' => $usuario->last_login_at,
                'empleado' => $usuario->empleado
            ]
        ], 200);
    }

    /**
     * Cambiar contraseña
     */
    public function cambiarContraseña(Request $request)
    {
        $request->validate([
            'ContraseñaActual' => 'required',
            'ContraseñaNueva' => 'required|min:6|confirmed'
        ]);

        $usuario = $request->user();

        if (!Hash::check($request->ContraseñaActual, $usuario->Contraseña)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta'
            ], 422);
        }

        $usuario->update([
            'Contraseña' => $request->ContraseñaNueva
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada exitosamente'
        ], 200);
    }

    /**
     * Revocar todos los tokens del usuario
     */
    public function revocarTodos(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Todos los tokens han sido revocados'
        ], 200);
    }
}
