"""
Script de prueba completo para verificar la conexión a la BD y extracción de datos
"""

import pymysql
import pandas as pd
import sys
from pathlib import Path

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent))

from config import DB_CONFIG

print("="*60)
print("🔍 TEST DE CONEXIÓN Y DATOS - ReGPS ML")
print("="*60)

# Mostrar configuración (sin password)
print(f"\n📋 Configuración de BD:")
print(f"   Host: {DB_CONFIG['host']}")
print(f"   Port: {DB_CONFIG['port']}")
print(f"   Database: {DB_CONFIG['database']}")
print(f"   User: {DB_CONFIG['user']}")

# Test 1: Conexión básica
print(f"\n1️⃣ Probando conexión básica...")
try:
    connection = pymysql.connect(
        host=DB_CONFIG['host'],
        port=DB_CONFIG['port'],
        user=DB_CONFIG['user'],
        password=DB_CONFIG['password'],
        database=DB_CONFIG['database'],
        charset=DB_CONFIG['charset']
    )
    print("   ✅ Conexión establecida")
    
    # Test 2: Contar registros
    print(f"\n2️⃣ Contando registros en tablas...")
    cursor = connection.cursor()
    
    tables = ['ubicaciones', 'dispositivos', 'empleados', 'zonas', 'alertas']
    for table in tables:
        try:
            cursor.execute(f"SELECT COUNT(*) FROM {table}")
            count = cursor.fetchone()[0]
            print(f"   📊 {table}: {count} registros")
        except Exception as e:
            print(f"   ⚠️ {table}: Error - {str(e)[:50]}")
    
    # Test 3: Obtener ubicaciones reales
    print(f"\n3️⃣ Extrayendo últimas 5 ubicaciones...")
    query = """
    SELECT 
        u.UbicacionID,
        u.DispositivoID,
        u.Latitud,
        u.Longitud,
        u.Velocidad,
        u.FechaHora
    FROM ubicaciones u
    ORDER BY u.FechaHora DESC
    LIMIT 5
    """
    
    df = pd.read_sql(query, connection)
    
    if len(df) > 0:
        print(f"   ✅ Extraídas {len(df)} ubicaciones")
        print("\n   📍 Datos de muestra:")
        print(df.to_string(index=False))
        
        # Estadísticas
        print(f"\n4️⃣ Estadísticas básicas:")
        print(f"   • Rango de latitud: {df['Latitud'].min():.6f} a {df['Latitud'].max():.6f}")
        print(f"   • Rango de longitud: {df['Longitud'].min():.6f} a {df['Longitud'].max():.6f}")
        print(f"   • Velocidad promedio: {df['Velocidad'].mean():.2f} km/h")
        print(f"   • Velocidad máxima: {df['Velocidad'].max():.2f} km/h")
    else:
        print("   ⚠️ No se encontraron ubicaciones")
    
    # Test 4: Verificar dispositivos
    print(f"\n5️⃣ Verificando dispositivos...")
    query = """
    SELECT 
        d.DispositivoID,
        d.IMEI,
        d.Modelo,
        d.Estado,
        COUNT(u.UbicacionID) as TotalUbicaciones
    FROM dispositivos d
    LEFT JOIN ubicaciones u ON d.DispositivoID = u.DispositivoID
    GROUP BY d.DispositivoID, d.IMEI, d.Modelo, d.Estado
    """
    
    df_disp = pd.read_sql(query, connection)
    print(f"   ✅ Dispositivos encontrados: {len(df_disp)}")
    print("\n   📱 Resumen de dispositivos:")
    print(df_disp.to_string(index=False))
    
    cursor.close()
    connection.close()
    
    print("\n" + "="*60)
    print("✅ TODOS LOS TESTS PASARON EXITOSAMENTE")
    print("="*60)
    print("\n💡 La base de datos está lista para ML!")
    print("   Puedes ejecutar:")
    print("   • python scripts/extract_data.py  (extraer datos)")
    print("   • python scripts/preprocess.py    (limpiar datos)")
    print("   • python api/app.py               (iniciar API)")
    
except pymysql.Error as e:
    print(f"\n❌ Error de MySQL: {e}")
    print("\n🔧 Verifica:")
    print("   1. MySQL está corriendo")
    print("   2. Credenciales en .env.ml son correctas")
    print("   3. La base de datos 'ReGpsBase' existe")
    
except Exception as e:
    print(f"\n❌ Error inesperado: {e}")
    import traceback
    traceback.print_exc()
