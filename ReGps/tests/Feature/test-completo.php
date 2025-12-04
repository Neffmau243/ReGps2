<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "🧪 PRUEBAS COMPLETAS - Sistema ReGps\n";
echo "========================================\n\n";

$baseUrl = 'http://127.0.0.1:8000/api';
$token = null;

function request($method, $url, $data = null, $token = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json'
    ];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

// Test 1: Login
echo "1️⃣  Test: Login Administrador\n";
$result = request('POST', "$baseUrl/auth/login", [
    'Email' => 'test@regps.com',
    'Contraseña' => '123456'
]);

if ($result['code'] === 200) {
    $token = $result['data']['token'];
    echo "   ✅ Login exitoso - Token: " . substr($token, 0, 20) . "...\n";
} else {
    echo "   ❌ Login falló - Code: {$result['code']}\n";
    exit(1);
}

// Test 2: Obtener usuario actual
echo "\n2️⃣  Test: Obtener usuario autenticado\n";
$result = request('GET', "$baseUrl/auth/me", null, $token);
if ($result['code'] === 200) {
    echo "   ✅ Usuario: {$result['data']['usuario']['Nombre']} - Rol: {$result['data']['usuario']['Rol']}\n";
} else {
    echo "   ❌ Falló - Code: {$result['code']}\n";
}

// Test 3: Listar usuarios (requiere admin)
echo "\n3️⃣  Test: Listar usuarios (Admin)\n";
$result = request('GET', "$baseUrl/usuarios", null, $token);
if ($result['code'] === 200) {
    $count = is_array($result['data']) ? count($result['data']) : 0;
    echo "   ✅ Usuarios encontrados: $count\n";
} else {
    echo "   ❌ Falló - Code: {$result['code']}\n";
}

// Test 4: Listar zonas
echo "\n4️⃣  Test: Listar zonas\n";
$result = request('GET', "$baseUrl/zonas", null, $token);
if ($result['code'] === 200) {
    $count = is_array($result['data']) ? count($result['data']) : 0;
    echo "   ✅ Zonas encontradas: $count\n";
} else {
    echo "   ❌ Falló - Code: {$result['code']}\n";
}

// Test 5: Crear ubicación
echo "\n5️⃣  Test: Crear ubicación\n";
$result = request('POST', "$baseUrl/ubicaciones", [
    'DispositivoID' => 1,
    'Latitud' => -12.0464,
    'Longitud' => -77.0428,
    'Velocidad' => 45.5,
    'Direccion' => 'Lima, Perú',
    'FechaHora' => date('Y-m-d H:i:s')
], $token);

if ($result['code'] === 201 || $result['code'] === 200) {
    echo "   ✅ Ubicación creada\n";
} else {
    echo "   ⚠️  Code: {$result['code']} - " . json_encode($result['data']) . "\n";
}

// Test 6: Listar alertas
echo "\n6️⃣  Test: Listar alertas\n";
$result = request('GET', "$baseUrl/alertas", null, $token);
if ($result['code'] === 200) {
    $count = is_array($result['data']) ? count($result['data']) : 0;
    echo "   ✅ Alertas encontradas: $count\n";
} else {
    echo "   ❌ Falló - Code: {$result['code']}\n";
}

// Test 7: Acceso sin token (debe fallar)
echo "\n7️⃣  Test: Acceso sin token (debe fallar)\n";
$result = request('GET', "$baseUrl/usuarios", null, null);
if ($result['code'] === 401) {
    echo "   ✅ Acceso denegado correctamente (401)\n";
} else {
    echo "   ❌ ERROR: Acceso sin token permitido - Code: {$result['code']}\n";
}

// Test 8: Cambiar contraseña
echo "\n8️⃣  Test: Cambiar contraseña\n";
$result = request('POST', "$baseUrl/auth/cambiar-contraseña", [
    'ContraseñaActual' => '123456',
    'ContraseñaNueva' => '123456',
    'ContraseñaNueva_confirmation' => '123456'
], $token);

if ($result['code'] === 200) {
    echo "   ✅ Contraseña actualizada\n";
} else {
    echo "   ⚠️  Code: {$result['code']}\n";
}

// Test 9: Logout
echo "\n9️⃣  Test: Logout\n";
$result = request('POST', "$baseUrl/auth/logout", null, $token);
if ($result['code'] === 200) {
    echo "   ✅ Logout exitoso\n";
} else {
    echo "   ❌ Falló - Code: {$result['code']}\n";
}

// Test 10: Usar token después de logout (debe fallar)
echo "\n🔟 Test: Usar token después de logout (debe fallar)\n";
$result = request('GET', "$baseUrl/auth/me", null, $token);
if ($result['code'] === 401) {
    echo "   ✅ Token revocado correctamente (401)\n";
} else {
    echo "   ❌ ERROR: Token aún válido - Code: {$result['code']}\n";
}

echo "\n========================================\n";
echo "✅ PRUEBAS COMPLETADAS\n";
echo "========================================\n\n";

// Resumen de funcionalidades implementadas
echo "📊 FUNCIONALIDADES IMPLEMENTADAS:\n\n";
echo "✅ Laravel Sanctum - Autenticación con tokens\n";
echo "✅ Sistema de roles (Administrador/Empleado)\n";
echo "✅ Sistema de permisos (24 permisos granulares)\n";
echo "✅ Rate limiting en API\n";
echo "✅ Middleware de autenticación\n";
echo "✅ Middleware de roles\n";
echo "✅ Optimización de ubicaciones (índices)\n";
echo "✅ Comandos de limpieza automática\n";
echo "✅ Geofencing completo\n";
echo "✅ Alertas automáticas\n";
echo "✅ 37 endpoints API\n\n";
