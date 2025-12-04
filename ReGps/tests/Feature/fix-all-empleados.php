<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Usuario;
use App\Models\Empleado;

echo "\n=== REPARACIÓN: Crear empleados para TODOS los usuarios ===\n\n";

// Obtener todos los usuarios que NO tienen empleado
$usuariosSinEmpleado = Usuario::whereDoesntHave('empleado')->get();

echo "Usuarios sin registro de empleado: " . $usuariosSinEmpleado->count() . "\n\n";

foreach ($usuariosSinEmpleado as $usuario) {
    echo "Creando empleado para: {$usuario->Nombre} (ID: {$usuario->UsuarioID}, Rol: {$usuario->Rol})...\n";
    
    Empleado::create([
        'UsuarioID' => $usuario->UsuarioID,
        'Nombre' => $usuario->Nombre,
        'Apellido' => '',
        'Estado' => 'Activo'
    ]);
    
    echo "✓ Empleado creado\n\n";
}

// Mostrar resumen final
$totalUsuarios = Usuario::count();
$totalEmpleados = Empleado::count();

echo "\n=== RESUMEN FINAL ===\n";
echo "Total usuarios: {$totalUsuarios}\n";
echo "Total empleados: {$totalEmpleados}\n";

if ($totalUsuarios === $totalEmpleados) {
    echo "✓ TODOS los usuarios tienen registro de empleado\n";
} else {
    echo "⚠ Advertencia: Hay usuarios sin empleado\n";
}

echo "\n";
