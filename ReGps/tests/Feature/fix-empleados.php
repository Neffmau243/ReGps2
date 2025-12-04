<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ARREGLANDO USUARIOS SIN EMPLEADO ===" . PHP_EOL . PHP_EOL;

// Obtener todos los usuarios con rol Empleado que no tienen registro en empleados
$usuarios = App\Models\Usuario::where('Rol', 'Empleado')
    ->whereDoesntHave('empleado')
    ->get();

echo "Usuarios con rol Empleado sin registro en empleados: " . $usuarios->count() . PHP_EOL . PHP_EOL;

foreach ($usuarios as $user) {
    echo "Creando empleado para: {$user->Nombre} ({$user->Email})" . PHP_EOL;
    
    $empleado = App\Models\Empleado::create([
        'UsuarioID' => $user->UsuarioID,
        'Nombre' => $user->Nombre,
        'Apellido' => '', // Vacío, se puede editar después
        'Estado' => 'Activo'
    ]);
    
    echo "✓ Empleado creado con ID: {$empleado->EmpleadoID}" . PHP_EOL . PHP_EOL;
}

echo "=== PROCESO COMPLETADO ===" . PHP_EOL;
echo PHP_EOL;

// Mostrar resumen final
echo "=== RESUMEN FINAL ===" . PHP_EOL;
$totalUsuarios = App\Models\Usuario::count();
$totalEmpleados = App\Models\Empleado::count();
$usuariosEmpleado = App\Models\Usuario::where('Rol', 'Empleado')->count();

echo "Total usuarios: {$totalUsuarios}" . PHP_EOL;
echo "Total empleados: {$totalEmpleados}" . PHP_EOL;
echo "Usuarios con rol Empleado: {$usuariosEmpleado}" . PHP_EOL;
