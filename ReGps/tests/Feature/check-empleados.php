<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$empleados = App\Models\Empleado::with('usuario')->get();

echo "Total empleados en BD: " . $empleados->count() . PHP_EOL . PHP_EOL;

foreach ($empleados as $emp) {
    echo "ID: {$emp->EmpleadoID}" . PHP_EOL;
    echo "Nombre: {$emp->Nombre} {$emp->Apellido}" . PHP_EOL;
    echo "UsuarioID: {$emp->UsuarioID}" . PHP_EOL;
    echo "Usuario: " . ($emp->usuario ? $emp->usuario->Nombre : 'Sin usuario') . PHP_EOL;
    echo "---" . PHP_EOL;
}
