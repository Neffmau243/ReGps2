<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permiso)
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'message' => 'No autenticado'
            ], 401);
        }

        // Administradores tienen todos los permisos
        if ($usuario->esAdministrador()) {
            return $next($request);
        }

        if (!$usuario->tienePermiso($permiso)) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción'
            ], 403);
        }

        return $next($request);
    }
}
