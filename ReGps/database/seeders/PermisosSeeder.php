<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permiso;
use App\Models\RolPermiso;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Usuarios
            ['Nombre' => 'usuarios.ver', 'Descripcion' => 'Ver usuarios', 'Grupo' => 'usuarios'],
            ['Nombre' => 'usuarios.crear', 'Descripcion' => 'Crear usuarios', 'Grupo' => 'usuarios'],
            ['Nombre' => 'usuarios.editar', 'Descripcion' => 'Editar usuarios', 'Grupo' => 'usuarios'],
            ['Nombre' => 'usuarios.eliminar', 'Descripcion' => 'Eliminar usuarios', 'Grupo' => 'usuarios'],
            
            // Empleados
            ['Nombre' => 'empleados.ver', 'Descripcion' => 'Ver empleados', 'Grupo' => 'empleados'],
            ['Nombre' => 'empleados.crear', 'Descripcion' => 'Crear empleados', 'Grupo' => 'empleados'],
            ['Nombre' => 'empleados.editar', 'Descripcion' => 'Editar empleados', 'Grupo' => 'empleados'],
            ['Nombre' => 'empleados.eliminar', 'Descripcion' => 'Eliminar empleados', 'Grupo' => 'empleados'],
            
            // Dispositivos
            ['Nombre' => 'dispositivos.ver', 'Descripcion' => 'Ver dispositivos', 'Grupo' => 'dispositivos'],
            ['Nombre' => 'dispositivos.crear', 'Descripcion' => 'Crear dispositivos', 'Grupo' => 'dispositivos'],
            ['Nombre' => 'dispositivos.editar', 'Descripcion' => 'Editar dispositivos', 'Grupo' => 'dispositivos'],
            ['Nombre' => 'dispositivos.eliminar', 'Descripcion' => 'Eliminar dispositivos', 'Grupo' => 'dispositivos'],
            
            // Ubicaciones
            ['Nombre' => 'ubicaciones.ver', 'Descripcion' => 'Ver ubicaciones', 'Grupo' => 'ubicaciones'],
            ['Nombre' => 'ubicaciones.crear', 'Descripcion' => 'Crear ubicaciones', 'Grupo' => 'ubicaciones'],
            ['Nombre' => 'ubicaciones.ver_propias', 'Descripcion' => 'Ver solo sus ubicaciones', 'Grupo' => 'ubicaciones'],
            
            // Zonas
            ['Nombre' => 'zonas.ver', 'Descripcion' => 'Ver zonas', 'Grupo' => 'zonas'],
            ['Nombre' => 'zonas.crear', 'Descripcion' => 'Crear zonas', 'Grupo' => 'zonas'],
            ['Nombre' => 'zonas.editar', 'Descripcion' => 'Editar zonas', 'Grupo' => 'zonas'],
            ['Nombre' => 'zonas.eliminar', 'Descripcion' => 'Eliminar zonas', 'Grupo' => 'zonas'],
            
            // Alertas
            ['Nombre' => 'alertas.ver', 'Descripcion' => 'Ver alertas', 'Grupo' => 'alertas'],
            ['Nombre' => 'alertas.crear', 'Descripcion' => 'Crear alertas', 'Grupo' => 'alertas'],
            ['Nombre' => 'alertas.editar', 'Descripcion' => 'Editar alertas', 'Grupo' => 'alertas'],
            ['Nombre' => 'alertas.eliminar', 'Descripcion' => 'Eliminar alertas', 'Grupo' => 'alertas'],
        ];

        foreach ($permisos as $permiso) {
            Permiso::create($permiso);
        }

        // Asignar TODOS los permisos a Administrador
        $todosPermisos = Permiso::all();
        foreach ($todosPermisos as $permiso) {
            RolPermiso::create([
                'Rol' => 'Administrador',
                'PermisoID' => $permiso->PermisoID
            ]);
        }

        // Asignar permisos limitados a Empleado
        $permisosEmpleado = [
            'ubicaciones.ver_propias',
            'ubicaciones.crear',
            'zonas.ver',
            'alertas.ver',
            'dispositivos.ver'
        ];

        foreach ($permisosEmpleado as $nombrePermiso) {
            $permiso = Permiso::where('Nombre', $nombrePermiso)->first();
            if ($permiso) {
                RolPermiso::create([
                    'Rol' => 'Empleado',
                    'PermisoID' => $permiso->PermisoID
                ]);
            }
        }
    }
}
