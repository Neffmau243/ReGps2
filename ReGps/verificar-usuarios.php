<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "           VERIFICACIÓN DE USUARIOS - ReGPS                    \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";

try {
    $usuarios = Usuario::all();
    
    if ($usuarios->isEmpty()) {
        echo "❌ No hay usuarios registrados en el sistema.\n\n";
        exit(1);
    }
    
    echo "📊 TOTAL DE USUARIOS: " . $usuarios->count() . "\n\n";
    echo "───────────────────────────────────────────────────────────────\n";
    
    foreach ($usuarios as $user) {
        echo "\n";
        echo "👤 USUARIO #" . $user->id . "\n";
        echo "───────────────────────────────────────────────────────────────\n";
        echo "   📧 Email:          " . $user->email . "\n";
        echo "   👤 Nombre:         " . $user->name . "\n";
        echo "   🔑 Rol:            " . ($user->rol ?? 'No definido') . "\n";
        echo "   📅 Creado:         " . $user->created_at->format('d/m/Y H:i:s') . "\n";
        echo "   🔄 Actualizado:    " . $user->updated_at->format('d/m/Y H:i:s') . "\n";
        
        // Información del hash (sin mostrar la contraseña completa por seguridad)
        if ($user->password) {
            $hashPreview = substr($user->password, 0, 20) . "...";
            echo "   🔒 Hash Password:  " . $hashPreview . "\n";
            echo "   🔐 Algoritmo:      " . (str_starts_with($user->password, '$2y$') ? 'bcrypt' : 'otro') . "\n";
        } else {
            echo "   ❌ Password:       No definida\n";
        }
        
        echo "───────────────────────────────────────────────────────────────\n";
    }
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "📈 RESUMEN:\n";
    echo "───────────────────────────────────────────────────────────────\n";
    
    // Contar por roles
    $roles = $usuarios->groupBy('rol');
    foreach ($roles as $rol => $users) {
        $rolName = $rol ?? 'Sin rol';
        echo "   " . $rolName . ": " . $users->count() . " usuario(s)\n";
    }
    
    echo "\n";
    
    // Verificar contraseñas
    echo "🔐 VERIFICACIÓN DE CONTRASEÑAS:\n";
    echo "───────────────────────────────────────────────────────────────\n";
    
    $passwordsValidas = 0;
    $passwordsInvalidas = 0;
    
    foreach ($usuarios as $user) {
        if ($user->password && strlen($user->password) > 0) {
            $passwordsValidas++;
        } else {
            $passwordsInvalidas++;
            echo "   ⚠️  Usuario #{$user->id} ({$user->email}) no tiene contraseña\n";
        }
    }
    
    echo "\n";
    echo "   ✅ Usuarios con contraseña válida: " . $passwordsValidas . "\n";
    echo "   ❌ Usuarios sin contraseña: " . $passwordsInvalidas . "\n";
    
    echo "\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "💡 INFORMACIÓN ADICIONAL:\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "   Las contraseñas están hasheadas con bcrypt\n";
    echo "   No es posible ver las contraseñas en texto plano\n";
    echo "   Para probar login, usa las credenciales de los seeders\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\n";
    
    // Mostrar credenciales de prueba si existen
    echo "🔑 CREDENCIALES DE PRUEBA (desde seeders):\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "   Email: admin@regps.com\n";
    echo "   Password: password\n";
    echo "───────────────────────────────────────────────────────────────\n";
    echo "\n";
    
    echo "✅ Verificación completada exitosamente.\n\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . "\n";
    echo "📍 Línea: " . $e->getLine() . "\n\n";
    exit(1);
}
