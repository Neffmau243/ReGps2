<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== USUARIOS ===" . PHP_EOL;
$usuarios = App\Models\Usuario::all();
echo "Total usuarios: " . $usuarios->count() . PHP_EOL . PHP_EOL;

foreach ($usuarios as $user) {
    $empleado = App\Models\Empleado::where('UsuarioID', $user->UsuarioID)->first();
    echo "ID: {$user->UsuarioID} - {$user->Nombre} ({$user->Email}) - Rol: {$user->Rol}";
    if ($empleado) {
        echo " → TIENE EMPLEADO (ID: {$empleado->EmpleadoID})";
    } else {
        echo " → SIN EMPLEADO";
    }
    echo PHP_EOL;
}

echo PHP_EOL . "=== EMPLEADOS ===" . PHP_EOL;
$empleados = App\Models\Empleado::with('usuario')->get();
echo "Total empleados: " . $empleados->count() . PHP_EOL . PHP_EOL;

foreach ($empleados as $emp) {
    echo "EmpleadoID: {$emp->EmpleadoID}" . PHP_EOL;
    echo "Nombre: {$emp->Nombre} {$emp->Apellido}" . PHP_EOL;
    echo "UsuarioID: {$emp->UsuarioID}" . PHP_EOL;
    echo "Usuario: " . ($emp->usuario ? "{$emp->usuario->Nombre} ({$emp->usuario->Email})" : 'Sin usuario') . PHP_EOL;
    echo "---" . PHP_EOL;
}
