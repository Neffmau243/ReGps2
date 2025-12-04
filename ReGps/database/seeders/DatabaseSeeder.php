<?php

namespace Database\Seeders;

use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Dispositivo;
use App\Models\Ubicacion;
use App\Models\Alerta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuarios
        $admin = Usuario::create([
            'Nombre' => 'Administrador',
            'Email' => 'admin@regps.com',
            'Contraseña' => Hash::make('admin123'),
            'Rol' => 'Administrador',
            'Estado' => 'Activo'
        ]);

        $userJuan = Usuario::create([
            'Nombre' => 'Juan',
            'Email' => 'juan@regps.com',
            'Contraseña' => Hash::make('juan123'),
            'Rol' => 'Empleado',
            'Estado' => 'Activo'
        ]);

        $userMaria = Usuario::create([
            'Nombre' => 'Maria',
            'Email' => 'maria@regps.com',
            'Contraseña' => Hash::make('maria123'),
            'Rol' => 'Empleado',
            'Estado' => 'Activo'
        ]);

        // Crear empleados
        $empleadoJuan = Empleado::create([
            'UsuarioID' => $userJuan->UsuarioID,
            'Nombre' => 'Juan',
            'Apellido' => 'Pérez',
            'Telefono' => '555-0101',
            'Direccion' => 'Calle Principal 123',
            'Estado' => 'Activo'
        ]);

        $empleadoMaria = Empleado::create([
            'UsuarioID' => $userMaria->UsuarioID,
            'Nombre' => 'Maria',
            'Apellido' => 'González',
            'Telefono' => '555-0102',
            'Direccion' => 'Avenida Central 456',
            'Estado' => 'Activo'
        ]);

        // Crear dispositivos
        $dispositivo1 = Dispositivo::create([
            'EmpleadoID' => $empleadoJuan->EmpleadoID,
            'IMEI' => '123456789012345',
            'Modelo' => 'GPS Tracker Pro',
            'Marca' => 'TechGPS',
            'Estado' => 'Activo',
            'FechaAsignacion' => now()
        ]);

        $dispositivo2 = Dispositivo::create([
            'EmpleadoID' => $empleadoMaria->EmpleadoID,
            'IMEI' => '987654321098765',
            'Modelo' => 'GPS Tracker Lite',
            'Marca' => 'TechGPS',
            'Estado' => 'Activo',
            'FechaAsignacion' => now()
        ]);

        // Crear ubicaciones
        Ubicacion::create([
            'DispositivoID' => $dispositivo1->DispositivoID,
            'Latitud' => 19.432608,
            'Longitud' => -99.133209,
            'Velocidad' => 45.50,
            'Direccion' => 'Ciudad de México, CDMX',
            'FechaHora' => now()
        ]);

        Ubicacion::create([
            'DispositivoID' => $dispositivo2->DispositivoID,
            'Latitud' => 19.426097,
            'Longitud' => -99.167764,
            'Velocidad' => 30.00,
            'Direccion' => 'Polanco, CDMX',
            'FechaHora' => now()
        ]);

        // Crear alertas
        Alerta::create([
            'DispositivoID' => $dispositivo1->DispositivoID,
            'TipoAlerta' => 'Velocidad',
            'Descripcion' => 'Velocidad excedida: 120 km/h',
            'FechaHora' => now(),
            'Estado' => 'Pendiente'
        ]);

        Alerta::create([
            'DispositivoID' => $dispositivo2->DispositivoID,
            'TipoAlerta' => 'Bateria',
            'Descripcion' => 'Batería baja: 15%',
            'FechaHora' => now(),
            'Estado' => 'Revisada'
        ]);
    }
}
