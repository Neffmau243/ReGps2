<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Dispositivo;

class UsuariosTestSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario Administrador
        $admin = Usuario::create([
            'Nombre' => 'Admin Sistema',
            'Email' => 'admin@regps.com',
            'Contraseña' => '123456', // Se hasheará automáticamente
            'Rol' => 'Administrador',
            'Estado' => 'Activo'
        ]);

        // Usuario Empleado
        $empleadoUser = Usuario::create([
            'Nombre' => 'Juan Pérez',
            'Email' => 'juan@regps.com',
            'Contraseña' => '123456',
            'Rol' => 'Empleado',
            'Estado' => 'Activo'
        ]);

        // Crear empleado asociado
        $empleado = Empleado::create([
            'UsuarioID' => $empleadoUser->UsuarioID,
            'Nombre' => 'Juan',
            'Apellido' => 'Pérez',
            'Telefono' => '987654321',
            'Direccion' => 'Av. Principal 123, Lima',
            'Estado' => 'Activo'
        ]);

        // Crear dispositivo para el empleado
        Dispositivo::create([
            'EmpleadoID' => $empleado->EmpleadoID,
            'IMEI' => '123456789012345',
            'Modelo' => 'GPS Tracker Pro',
            'Marca' => 'TechGPS',
            'Estado' => 'Activo',
            'FechaAsignacion' => now()
        ]);

        $this->command->info('✓ Usuarios de prueba creados:');
        $this->command->info('  Admin: admin@regps.com / 123456');
        $this->command->info('  Empleado: juan@regps.com / 123456');
    }
}
