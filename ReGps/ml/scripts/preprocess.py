"""
Script para preprocesar y limpiar datos extraídos de la BD.

Tareas:
- Eliminar duplicados
- Manejar valores faltantes
- Filtrar datos anómalos
- Ordenar por tiempo
- Agrupar por dispositivo

Uso:
    python scripts/preprocess.py
"""

import sys
from pathlib import Path
import pandas as pd
import numpy as np
from datetime import datetime

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from config import DATA_DIR

def load_latest_raw_data():
    """
    Carga el archivo de ubicaciones más reciente
    
    Returns:
        DataFrame con ubicaciones crudas
    """
    raw_dir = Path(DATA_DIR) / 'raw'
    
    # Buscar archivos de ubicaciones
    ubicaciones_files = list(raw_dir.glob('ubicaciones_raw_*.csv'))
    
    if not ubicaciones_files:
        print("❌ No se encontraron archivos de ubicaciones en data/raw/")
        return None
    
    # Tomar el más reciente
    latest_file = max(ubicaciones_files, key=lambda x: x.stat().st_mtime)
    print(f"📂 Cargando: {latest_file.name}")
    
    df = pd.read_csv(latest_file)
    print(f"✅ Cargadas {len(df):,} filas")
    
    return df


def clean_ubicaciones(df):
    """
    Limpia y preprocesa datos de ubicaciones
    
    Args:
        df: DataFrame con ubicaciones crudas
    
    Returns:
        DataFrame limpio
    """
    print("\n🧹 Limpiando datos...")
    
    df_clean = df.copy()
    initial_count = len(df_clean)
    
    # 1. Eliminar duplicados exactos
    df_clean = df_clean.drop_duplicates()
    print(f"  • Duplicados eliminados: {initial_count - len(df_clean)}")
    
    # 2. Convertir FechaHora a datetime
    df_clean['FechaHora'] = pd.to_datetime(df_clean['FechaHora'])
    
    # 3. Eliminar filas sin coordenadas
    before = len(df_clean)
    df_clean = df_clean.dropna(subset=['Latitud', 'Longitud'])
    print(f"  • Filas sin coordenadas: {before - len(df_clean)}")
    
    # 4. Filtrar coordenadas imposibles
    # Latitud válida: -90 a 90, Longitud válida: -180 a 180
    before = len(df_clean)
    df_clean = df_clean[
        (df_clean['Latitud'].between(-90, 90)) &
        (df_clean['Longitud'].between(-180, 180))
    ]
    print(f"  • Coordenadas inválidas: {before - len(df_clean)}")
    
    # 5. Filtrar velocidades imposibles (ej: > 200 km/h)
    before = len(df_clean)
    df_clean = df_clean[
        (df_clean['Velocidad'].isna()) | 
        (df_clean['Velocidad'] <= 200)
    ]
    print(f"  • Velocidades anómalas (>200 km/h): {before - len(df_clean)}")
    
    # 6. Rellenar velocidades faltantes con 0
    df_clean['Velocidad'] = df_clean['Velocidad'].fillna(0)
    
    # 7. Rellenar dirección faltante con 0
    df_clean['Direccion'] = df_clean['Direccion'].fillna(0)
    
    # 8. Ordenar por dispositivo y tiempo
    df_clean = df_clean.sort_values(['DispositivoID', 'FechaHora'])
    
    # 9. Resetear índice
    df_clean = df_clean.reset_index(drop=True)
    
    print(f"\n✅ Limpieza completada: {len(df_clean):,} filas finales")
    print(f"   Reducción: {((initial_count - len(df_clean)) / initial_count * 100):.1f}%")
    
    return df_clean


def add_basic_features(df):
    """
    Agrega features básicas para análisis
    
    Args:
        df: DataFrame limpio
    
    Returns:
        DataFrame con features adicionales
    """
    print("\n🔧 Agregando features básicas...")
    
    df_features = df.copy()
    
    # Features temporales
    df_features['Hora'] = df_features['FechaHora'].dt.hour
    df_features['DiaSemana'] = df_features['FechaHora'].dt.dayofweek  # 0=Lunes, 6=Domingo
    df_features['DiaMes'] = df_features['FechaHora'].dt.day
    df_features['Mes'] = df_features['FechaHora'].dt.month
    
    # Categorías horarias
    df_features['PeriodoHorario'] = pd.cut(
        df_features['Hora'],
        bins=[0, 6, 12, 18, 24],
        labels=['Madrugada', 'Mañana', 'Tarde', 'Noche'],
        include_lowest=True
    )
    
    # Es fin de semana?
    df_features['EsFinDeSemana'] = (df_features['DiaSemana'] >= 5).astype(int)
    
    # Categorías de velocidad
    df_features['CategoriaVelocidad'] = pd.cut(
        df_features['Velocidad'],
        bins=[0, 10, 40, 80, 200],
        labels=['Detenido', 'Lento', 'Moderado', 'Rapido'],
        include_lowest=True
    )
    
    print(f"✅ Features agregadas: Hora, DiaSemana, PeriodoHorario, EsFinDeSemana, CategoriaVelocidad")
    
    return df_features


def save_processed_data(df, filename='ubicaciones_processed.csv'):
    """
    Guarda datos procesados
    
    Args:
        df: DataFrame procesado
        filename: Nombre del archivo de salida
    """
    processed_dir = Path(DATA_DIR) / 'processed'
    processed_dir.mkdir(parents=True, exist_ok=True)
    
    # Agregar timestamp al nombre
    timestamp = datetime.now().strftime("%Y%m%d")
    output_path = processed_dir / f"{filename.replace('.csv', '')}_{timestamp}.csv"
    
    df.to_csv(output_path, index=False, encoding='utf-8')
    print(f"\n💾 Datos guardados en: {output_path}")
    
    return output_path


def generate_summary_stats(df):
    """
    Genera estadísticas resumidas de los datos
    
    Args:
        df: DataFrame procesado
    """
    print("\n" + "=" * 60)
    print("📊 ESTADÍSTICAS DE LOS DATOS PROCESADOS")
    print("=" * 60)
    
    print(f"\n📍 Total de ubicaciones: {len(df):,}")
    print(f"📱 Dispositivos únicos: {df['DispositivoID'].nunique()}")
    print(f"👤 Empleados únicos: {df['EmpleadoNombre'].nunique()}")
    
    print(f"\n📅 Rango temporal:")
    print(f"  • Inicio: {df['FechaHora'].min()}")
    print(f"  • Fin: {df['FechaHora'].max()}")
    print(f"  • Duración: {(df['FechaHora'].max() - df['FechaHora'].min()).days} días")
    
    print(f"\n🚗 Velocidad:")
    print(f"  • Media: {df['Velocidad'].mean():.2f} km/h")
    print(f"  • Mediana: {df['Velocidad'].median():.2f} km/h")
    print(f"  • Máxima: {df['Velocidad'].max():.2f} km/h")
    
    print(f"\n🌍 Coordenadas:")
    print(f"  • Latitud: [{df['Latitud'].min():.6f}, {df['Latitud'].max():.6f}]")
    print(f"  • Longitud: [{df['Longitud'].min():.6f}, {df['Longitud'].max():.6f}]")
    
    print("\n" + "=" * 60)


def preprocess_pipeline():
    """
    Pipeline completo de preprocesamiento
    """
    print("=" * 60)
    print("🚀 PREPROCESAMIENTO DE DATOS")
    print("=" * 60)
    
    # 1. Cargar datos crudos
    df_raw = load_latest_raw_data()
    if df_raw is None:
        return False
    
    # 2. Limpiar datos
    df_clean = clean_ubicaciones(df_raw)
    
    # 3. Agregar features
    df_processed = add_basic_features(df_clean)
    
    # 4. Guardar datos procesados
    save_processed_data(df_processed)
    
    # 5. Generar estadísticas
    generate_summary_stats(df_processed)
    
    print("\n✅ Preprocesamiento completado exitosamente")
    
    return True


if __name__ == "__main__":
    preprocess_pipeline()
