<?php
/**
 * Script para verificar datos en la base de datos MySQL
 * Uso: php check_db.php
 */

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'ReGpsBase';
$username = 'root';
$password = '1234';

try {
    // Conectar a MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conexión exitosa a la base de datos: $dbname\n\n";
    
    // ========== DISPOSITIVOS ==========
    echo "📱 DISPOSITIVOS:\n";
    echo str_repeat("=", 80) . "\n";
    $stmt = $pdo->query("SELECT * FROM dispositivos ORDER BY DispositivoID");
    $dispositivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($dispositivos) > 0) {
        foreach ($dispositivos as $d) {
            echo sprintf(
                "ID: %d | Modelo: %s | IMEI: %s | Estado: %s | EmpleadoID: %s\n",
                $d['DispositivoID'],
                $d['Modelo'] ?? 'N/A',
                $d['IMEI'],
                $d['Estado'],
                $d['EmpleadoID'] ?? 'Sin asignar'
            );
        }
    } else {
        echo "⚠️  No hay dispositivos registrados\n";
    }
    
    // ========== ÚLTIMAS 10 UBICACIONES ==========
    echo "\n\n📍 ÚLTIMAS 10 UBICACIONES (más recientes primero):\n";
    echo str_repeat("=", 80) . "\n";
    $stmt = $pdo->query("
        SELECT 
            u.UbicacionID,
            u.DispositivoID,
            u.Latitud,
            u.Longitud,
            u.Velocidad,
            u.FechaHora,
            d.Modelo as NombreDispositivo
        FROM ubicaciones u
        LEFT JOIN dispositivos d ON u.DispositivoID = d.DispositivoID
        ORDER BY u.FechaHora DESC
        LIMIT 10
    ");
    $ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($ubicaciones) > 0) {
        foreach ($ubicaciones as $u) {
            echo sprintf(
                "ID: %d | Dispositivo: %s (ID:%d) | Lat: %s | Lng: %s | Vel: %s km/h | Fecha: %s\n",
                $u['UbicacionID'],
                $u['NombreDispositivo'] ?? 'Desconocido',
                $u['DispositivoID'],
                $u['Latitud'],
                $u['Longitud'],
                $u['Velocidad'],
                $u['FechaHora']
            );
        }
    } else {
        echo "⚠️  No hay ubicaciones registradas\n";
    }
    
    // ========== ESTADÍSTICAS ==========
    echo "\n\n📊 ESTADÍSTICAS:\n";
    echo str_repeat("=", 80) . "\n";
    
    // Total de ubicaciones
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM ubicaciones");
    $totalUbicaciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de ubicaciones registradas: $totalUbicaciones\n";
    
    // Total de dispositivos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM dispositivos");
    $totalDispositivos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Total de dispositivos: $totalDispositivos\n";
    
    // Ubicaciones por dispositivo
    echo "\nUbicaciones por dispositivo:\n";
    $stmt = $pdo->query("
        SELECT 
            d.DispositivoID,
            d.Modelo,
            COUNT(u.UbicacionID) as total_ubicaciones,
            MAX(u.FechaHora) as ultima_ubicacion
        FROM dispositivos d
        LEFT JOIN ubicaciones u ON d.DispositivoID = u.DispositivoID
        GROUP BY d.DispositivoID, d.Modelo
        ORDER BY total_ubicaciones DESC
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stats as $s) {
        echo sprintf(
            "  - %s (ID:%d): %d ubicaciones | Última: %s\n",
            $s['Modelo'] ?? 'Sin modelo',
            $s['DispositivoID'],
            $s['total_ubicaciones'],
            $s['ultima_ubicacion'] ?? 'Nunca'
        );
    }
    
    // ========== ZONAS ==========
    echo "\n\n🗺️  ZONAS:\n";
    echo str_repeat("=", 80) . "\n";
    $stmt = $pdo->query("SELECT * FROM zonas ORDER BY ZonaID");
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($zonas) > 0) {
        foreach ($zonas as $z) {
            echo sprintf(
                "ID: %d | Nombre: %s | Tipo: %s | Geometría: %s | Estado: %s\n",
                $z['ZonaID'],
                $z['Nombre'],
                $z['TipoZona'],
                $z['TipoGeometria'],
                $z['Estado']
            );
        }
    } else {
        echo "⚠️  No hay zonas registradas\n";
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "✅ Consulta completada exitosamente\n";
    
} catch (PDOException $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "\n";
    echo "\n💡 Verifica que:\n";
    echo "  - MySQL esté ejecutándose\n";
    echo "  - La base de datos 'ReGpsBase' exista\n";
    echo "  - El usuario 'root' tenga la contraseña '1234'\n";
    exit(1);
}
