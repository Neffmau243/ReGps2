<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Usuario;
use App\Models\Empleado;
use App\Models\Dispositivo;

echo "========================================\n";
echo "VERIFICACIÓN DE EMPLEADOS Y USUARIOS\n";
echo "========================================\n\n";

// Obtener todos los usuarios
$usuarios = Usuario::all();

echo "USUARIOS EN EL SISTEMA:\n";
echo "------------------------\n";
foreach ($usuarios as $usuario) {
    echo "ID: {$usuario->UsuarioID}\n";
    echo "Nombre: {$usuario->Nombre}\n";
    echo "Email: {$usuario->Email}\n";
    echo "Rol: {$usuario->Rol}\n";
    
    // Buscar si tiene un empleado vinculado
    $empleado = Empleado::where('UsuarioID', $usuario->UsuarioID)->first();
    
    if ($empleado) {
        echo "✓ Empleado vinculado: ID {$empleado->EmpleadoID}\n";
        echo "  - Cargo: {$empleado->Cargo}\n";
        echo "  - Teléfono: {$empleado->Telefono}\n";
        
        // Buscar dispositivos asignados
        $dispositivos = Dispositivo::where('EmpleadoID', $empleado->EmpleadoID)->get();
        echo "  - Dispositivos: " . $dispositivos->count() . "\n";
        
        foreach ($dispositivos as $dispositivo) {
            echo "    * {$dispositivo->Modelo} ({$dispositivo->IMEI}) - {$dispositivo->Estado}\n";
        }
    } else {
        echo "✗ No tiene empleado vinculado\n";
        
        // Si es rol Empleado, crear el registro
        if ($usuario->Rol === 'Empleado') {
            echo "  ⚠ Creando registro de empleado...\n";
            
            $nuevoEmpleado = Empleado::create([
                'UsuarioID' => $usuario->UsuarioID,
                'Nombre' => $usuario->Nombre,
                'Cargo' => 'Empleado',
                'Telefono' => '999999999',
                'FechaContratacion' => now()
            ]);
            
            echo "  ✓ Empleado creado con ID: {$nuevoEmpleado->EmpleadoID}\n";
        }
    }
    
    echo "\n";
}

echo "========================================\n";
echo "EMPLEADOS SIN USUARIO:\n";
echo "------------------------\n";

$empleadosSinUsuario = Empleado::whereNull('UsuarioID')
    ->orWhereNotIn('UsuarioID', Usuario::pluck('UsuarioID'))
    ->get();

if ($empleadosSinUsuario->count() > 0) {
    foreach ($empleadosSinUsuario as $empleado) {
        echo "ID: {$empleado->EmpleadoID}\n";
        echo "Nombre: {$empleado->Nombre}\n";
        echo "Cargo: {$empleado->Cargo}\n";
        echo "UsuarioID: " . ($empleado->UsuarioID ?? 'NULL') . "\n\n";
    }
} else {
    echo "✓ Todos los empleados tienen usuario asignado\n";
}

echo "\n========================================\n";
echo "RESUMEN:\n";
echo "------------------------\n";
echo "Total Usuarios: " . Usuario::count() . "\n";
echo "Total Empleados: " . Empleado::count() . "\n";
echo "Total Dispositivos: " . Dispositivo::count() . "\n";
echo "Dispositivos Asignados: " . Dispositivo::whereNotNull('EmpleadoID')->count() . "\n";
echo "Dispositivos Sin Asignar: " . Dispositivo::whereNull('EmpleadoID')->count() . "\n";
echo "========================================\n";

