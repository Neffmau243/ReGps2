"""
Script para extraer datos de la base de datos MySQL de Laravel
y exportarlos a archivos CSV para entrenamiento de modelos.

Uso:
    python scripts/extract_data.py
"""

import sys
import os
from pathlib import Path
from datetime import datetime, timedelta
import pandas as pd

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from utils.db_connector import DatabaseConnector
from config import DB_CONFIG, DATA_DIR

def extract_ubicaciones(connector, days_back=90, output_path=None):
    """
    Extrae ubicaciones de los últimos N días
    
    Args:
        connector: Instancia de DatabaseConnector
        days_back: Número de días hacia atrás para extraer
        output_path: Ruta del archivo CSV de salida
    
    Returns:
        DataFrame con las ubicaciones
    """
    print(f"\n📍 Extrayendo ubicaciones de los últimos {days_back} días...")
    
    fecha_limite = (datetime.now() - timedelta(days=days_back)).strftime('%Y-%m-%d')
    
    query = f"""
    SELECT 
        u.UbicacionID,
        u.DispositivoID,
        u.Latitud,
        u.Longitud,
        u.Velocidad,
        u.Direccion,
        u.FechaHora,
        d.IMEI,
        d.Modelo,
        e.Nombre as EmpleadoNombre
    FROM ubicaciones u
    LEFT JOIN dispositivos d ON u.DispositivoID = d.DispositivoID
    LEFT JOIN empleados e ON d.EmpleadoID = e.EmpleadoID
    WHERE u.FechaHora >= '{fecha_limite}'
    ORDER BY u.FechaHora ASC
    """
    
    df = connector.execute_query(query)
    
    if df is not None and len(df) > 0:
        print(f"✅ Extraídas {len(df):,} ubicaciones")
        print(f"📅 Rango: {df['FechaHora'].min()} a {df['FechaHora'].max()}")
        
        if output_path:
            df.to_csv(output_path, index=False, encoding='utf-8')
            print(f"💾 Guardado en: {output_path}")
    else:
        print("⚠️ No se encontraron ubicaciones")
    
    return df


def extract_dispositivos(connector, output_path=None):
    """
    Extrae información de dispositivos
    
    Args:
        connector: Instancia de DatabaseConnector
        output_path: Ruta del archivo CSV de salida
    
    Returns:
        DataFrame con los dispositivos
    """
    print("\n📱 Extrayendo información de dispositivos...")
    
    query = """
    SELECT 
        d.DispositivoID,
        d.IMEI,
        d.Modelo,
        d.Estado,
        d.EmpleadoID,
        e.Nombre as EmpleadoNombre,
        e.Apellido as EmpleadoApellido
    FROM dispositivos d
    LEFT JOIN empleados e ON d.EmpleadoID = e.EmpleadoID
    WHERE d.Estado = 'activo'
    """
    
    df = connector.execute_query(query)
    
    if df is not None and len(df) > 0:
        print(f"✅ Extraídos {len(df):,} dispositivos")
        
        if output_path:
            df.to_csv(output_path, index=False, encoding='utf-8')
            print(f"💾 Guardado en: {output_path}")
    else:
        print("⚠️ No se encontraron dispositivos")
    
    return df


def extract_zonas(connector, output_path=None):
    """
    Extrae información de zonas/geocercas
    
    Args:
        connector: Instancia de DatabaseConnector
        output_path: Ruta del archivo CSV de salida
    
    Returns:
        DataFrame con las zonas
    """
    print("\n🗺️ Extrayendo información de zonas...")
    
    query = """
    SELECT 
        ZonaID,
        Nombre,
        Latitud,
        Longitud,
        Radio,
        TipoAlerta
    FROM zonas
    """
    
    df = connector.execute_query(query)
    
    if df is not None and len(df) > 0:
        print(f"✅ Extraídas {len(df):,} zonas")
        
        if output_path:
            df.to_csv(output_path, index=False, encoding='utf-8')
            print(f"💾 Guardado en: {output_path}")
    else:
        print("⚠️ No se encontraron zonas")
    
    return df


def extract_all_data(days_back=90):
    """
    Extrae todos los datos necesarios para ML
    
    Args:
        days_back: Número de días de historial a extraer
    """
    print("=" * 60)
    print("🚀 EXTRACCIÓN DE DATOS PARA MACHINE LEARNING")
    print("=" * 60)
    
    # Crear directorio de salida si no existe
    raw_dir = Path(DATA_DIR) / 'raw'
    raw_dir.mkdir(parents=True, exist_ok=True)
    
    # Conectar a la base de datos
    connector = DatabaseConnector()
    
    try:
        if not connector.connect():
            print("❌ Error al conectar a la base de datos")
            return False
        
        # Extraer ubicaciones
        ubicaciones_path = raw_dir / f'ubicaciones_raw_{datetime.now().strftime("%Y%m%d")}.csv'
        df_ubicaciones = extract_ubicaciones(connector, days_back, ubicaciones_path)
        
        # Extraer dispositivos
        dispositivos_path = raw_dir / f'dispositivos_raw_{datetime.now().strftime("%Y%m%d")}.csv'
        df_dispositivos = extract_dispositivos(connector, dispositivos_path)
        
        # Extraer zonas
        zonas_path = raw_dir / f'zonas_raw_{datetime.now().strftime("%Y%m%d")}.csv'
        df_zonas = extract_zonas(connector, zonas_path)
        
        print("\n" + "=" * 60)
        print("✅ EXTRACCIÓN COMPLETADA")
        print("=" * 60)
        print(f"\n📊 Resumen:")
        print(f"  • Ubicaciones: {len(df_ubicaciones) if df_ubicaciones is not None else 0:,}")
        print(f"  • Dispositivos: {len(df_dispositivos) if df_dispositivos is not None else 0:,}")
        print(f"  • Zonas: {len(df_zonas) if df_zonas is not None else 0:,}")
        print(f"\n📂 Archivos guardados en: {raw_dir}")
        
        return True
        
    except Exception as e:
        print(f"❌ Error durante la extracción: {str(e)}")
        return False
    
    finally:
        connector.close()


if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description='Extraer datos de ReGPS para ML')
    parser.add_argument(
        '--days', 
        type=int, 
        default=90,
        help='Número de días de historial a extraer (default: 90)'
    )
    
    args = parser.parse_args()
    
    extract_all_data(days_back=args.days)
