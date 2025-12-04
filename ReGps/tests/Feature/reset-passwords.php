<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

echo "Reseteando contraseñas...\n\n";

// Admin
$admin = Usuario::where('Email', 'admin@regps.com')->first();
if ($admin) {
    // Actualizar directamente sin usar el mutator
    $admin->update(['Contraseña' => '123456']);
    echo "✓ Admin password reset: admin@regps.com / 123456\n";
    echo "  Hash: " . $admin->fresh()->Contraseña . "\n\n";
}

// Empleado
$empleado = Usuario::where('Email', 'juan@regps.com')->first();
if ($empleado) {
    $empleado->update(['Contraseña' => '123456']);
    echo "✓ Empleado password reset: juan@regps.com / 123456\n";
    echo "  Hash: " . $empleado->fresh()->Contraseña . "\n\n";
}

// Probar login
echo "Probando login...\n";
$testUser = Usuario::where('Email', 'admin@regps.com')->first();
$passwordCheck = Hash::check('123456', $testUser->Contraseña);
echo "Password check result: " . ($passwordCheck ? "✓ CORRECTO" : "✗ INCORRECTO") . "\n";
