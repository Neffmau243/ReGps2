<?php
/**
 * Script para insertar ubicaciones de prueba
 * Uso: php insert_test_location.php
 */

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'ReGpsBase';
$username = 'root';
$password = '1234';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado a la base de datos\n\n";
    
    // Dispositivo ID (actualmente usando el 2)
    $dispositivoID = 2;
    
    // Ubicaciones para simular un recorrido desde la ubicación actual hasta la nueva
    $ubicaciones = [
        // Ubicación actual (punto de inicio)
        ['lat' => -16.381771, 'lng' => -71.515007, 'vel' => 0, 'desc' => 'Punto inicial'],
        
        // Puntos intermedios para simular movimiento
        ['lat' => -16.385000, 'lng' => -71.517000, 'vel' => 15.5, 'desc' => 'En movimiento 1'],
        ['lat' => -16.390000, 'lng' => -71.520000, 'vel' => 22.3, 'desc' => 'En movimiento 2'],
        ['lat' => -16.395000, 'lng' => -71.525000, 'vel' => 18.7, 'desc' => 'En movimiento 3'],
        ['lat' => -16.400000, 'lng' => -71.530000, 'vel' => 25.1, 'desc' => 'En movimiento 4'],
        ['lat' => -16.405000, 'lng' => -71.535000, 'vel' => 20.4, 'desc' => 'En movimiento 5'],
        
        // Ubicación final (destino)
        ['lat' => -16.408973, 'lng' => -71.540430, 'vel' => 5.2, 'desc' => 'Destino alcanzado'],
        ['lat' => -16.408973, 'lng' => -71.540430, 'vel' => 0, 'desc' => 'Detenido en destino'],
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO ubicaciones 
        (DispositivoID, Latitud, Longitud, Velocidad, Direccion, FechaHora, created_at, updated_at)
        VALUES 
        (:dispositivo, :lat, :lng, :vel, :direccion, :fecha, :created, :updated)
    ");
    
    $baseTime = new DateTime();
    $count = 0;
    
    foreach ($ubicaciones as $index => $ubicacion) {
        // Calcular tiempo: cada ubicación con 2 minutos de diferencia
        $tiempo = clone $baseTime;
        $tiempo->modify("+{$index} minutes");
        $fechaHora = $tiempo->format('Y-m-d H:i:s');
        
        $stmt->execute([
            ':dispositivo' => $dispositivoID,
            ':lat' => $ubicacion['lat'],
            ':lng' => $ubicacion['lng'],
            ':vel' => $ubicacion['vel'],
            ':direccion' => $ubicacion['desc'],
            ':fecha' => $fechaHora,
            ':created' => $fechaHora,
            ':updated' => $fechaHora
        ]);
        
        echo sprintf(
            "✅ Insertado #%d: %s | Lat: %.6f | Lng: %.6f | Vel: %.1f km/h | Hora: %s\n",
            $index + 1,
            $ubicacion['desc'],
            $ubicacion['lat'],
            $ubicacion['lng'],
            $ubicacion['vel'],
            $fechaHora
        );
        
        $count++;
    }
    
    echo "\n🎉 Total insertado: $count ubicaciones para el Dispositivo #$dispositivoID\n";
    echo "📍 Coordenadas finales: Lat -16.408973, Lng -71.540430\n";
    echo "\n💡 Ahora ve al historial para ver el recorrido en el mapa!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
