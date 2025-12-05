"""
Feature Engineering: Creación de características avanzadas para ML

Features que se crean:
- Distancias entre puntos consecutivos
- Tiempos entre puntos
- Velocidades calculadas
- Aceleraciones
- Cambios de dirección
- Tiempo en movimiento vs detenido
- Patrones de ruta

Uso:
    python scripts/feature_engineering.py
"""

import sys
from pathlib import Path
import pandas as pd
import numpy as np
from datetime import datetime

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from utils.geo_utils import calculate_distance, calculate_bearing, calculate_speed, calculate_acceleration
from config import DATA_DIR


def load_processed_data():
    """
    Carga el archivo procesado más reciente
    
    Returns:
        DataFrame con ubicaciones procesadas
    """
    processed_dir = Path(DATA_DIR) / 'processed'
    
    # Buscar archivos procesados
    processed_files = list(processed_dir.glob('ubicaciones_processed_*.csv'))
    
    if not processed_files:
        print("❌ No se encontraron archivos procesados en data/processed/")
        print("   Ejecuta primero: python scripts/preprocess.py")
        return None
    
    # Tomar el más reciente
    latest_file = max(processed_files, key=lambda x: x.stat().st_mtime)
    print(f"📂 Cargando: {latest_file.name}")
    
    df = pd.read_csv(latest_file, parse_dates=['FechaHora'])
    print(f"✅ Cargadas {len(df):,} filas")
    
    return df


def calculate_trip_features(df):
    """
    Calcula features basadas en trayectorias por dispositivo
    
    Args:
        df: DataFrame con ubicaciones
    
    Returns:
        DataFrame con features de trayectoria
    """
    print("\n🛣️ Calculando features de trayectorias...")
    
    df_features = df.copy()
    
    # Agrupar por dispositivo y ordenar por tiempo
    df_features = df_features.sort_values(['DispositivoID', 'FechaHora'])
    
    # Calcular diferencias entre puntos consecutivos del mismo dispositivo
    grouped = df_features.groupby('DispositivoID')
    
    # Tiempo desde el punto anterior (en segundos)
    df_features['TiempoDesdeAnterior'] = grouped['FechaHora'].diff().dt.total_seconds()
    
    # Coordenadas anteriores
    df_features['LatitudAnterior'] = grouped['Latitud'].shift(1)
    df_features['LongitudAnterior'] = grouped['Longitud'].shift(1)
    
    # Velocidad y dirección anteriores
    df_features['VelocidadAnterior'] = grouped['Velocidad'].shift(1)
    df_features['DireccionAnterior'] = grouped['Direccion'].shift(1)
    
    # Calcular distancia al punto anterior
    print("  • Calculando distancias...")
    df_features['DistanciaRecorrida'] = df_features.apply(
        lambda row: calculate_distance(
            row['LatitudAnterior'], row['LongitudAnterior'],
            row['Latitud'], row['Longitud']
        ) if pd.notna(row['LatitudAnterior']) else 0,
        axis=1
    )
    
    # Calcular velocidad real (basada en distancia/tiempo)
    print("  • Calculando velocidades reales...")
    df_features['VelocidadCalculada'] = df_features.apply(
        lambda row: calculate_speed(
            row['DistanciaRecorrida'], 
            row['TiempoDesdeAnterior']
        ) if pd.notna(row['TiempoDesdeAnterior']) and row['TiempoDesdeAnterior'] > 0 else 0,
        axis=1
    )
    
    # Calcular aceleración
    print("  • Calculando aceleraciones...")
    df_features['Aceleracion'] = df_features.apply(
        lambda row: calculate_acceleration(
            row['VelocidadAnterior'], 
            row['Velocidad'],
            row['TiempoDesdeAnterior']
        ) if pd.notna(row['VelocidadAnterior']) and pd.notna(row['TiempoDesdeAnterior']) and row['TiempoDesdeAnterior'] > 0 else 0,
        axis=1
    )
    
    # Calcular cambio de dirección
    print("  • Calculando cambios de dirección...")
    df_features['CambioDireccion'] = (
        (df_features['Direccion'] - df_features['DireccionAnterior']).abs()
    )
    # Ajustar para el caso 359° -> 1° (debe ser 2° no 358°)
    df_features.loc[df_features['CambioDireccion'] > 180, 'CambioDireccion'] = (
        360 - df_features['CambioDireccion']
    )
    
    # Es una parada? (velocidad < 5 km/h)
    df_features['EsParada'] = (df_features['Velocidad'] < 5).astype(int)
    
    # Tiempo acumulado en movimiento por dispositivo
    df_features['TiempoMovimientoAcum'] = grouped.apply(
        lambda g: (g['TiempoDesdeAnterior'] * (1 - g['EsParada'])).cumsum()
    ).reset_index(level=0, drop=True)
    
    # Distancia acumulada por dispositivo
    df_features['DistanciaAcumulada'] = grouped['DistanciaRecorrida'].cumsum()
    
    print(f"✅ Features de trayectoria calculadas")
    
    return df_features


def calculate_statistical_features(df):
    """
    Calcula features estadísticas usando ventanas móviles
    
    Args:
        df: DataFrame con ubicaciones
    
    Returns:
        DataFrame con features estadísticas
    """
    print("\n📈 Calculando features estadísticas (ventanas móviles)...")
    
    df_features = df.copy()
    
    # Ventanas: últimos 5 y 10 puntos
    windows = [5, 10]
    
    for window in windows:
        print(f"  • Ventana de {window} puntos...")
        
        grouped = df_features.groupby('DispositivoID')
        
        # Velocidad promedio en ventana
        df_features[f'VelocidadMedia_{window}p'] = grouped['Velocidad'].transform(
            lambda x: x.rolling(window=window, min_periods=1).mean()
        )
        
        # Velocidad máxima en ventana
        df_features[f'VelocidadMax_{window}p'] = grouped['Velocidad'].transform(
            lambda x: x.rolling(window=window, min_periods=1).max()
        )
        
        # Desviación estándar de velocidad (variabilidad)
        df_features[f'VelocidadStd_{window}p'] = grouped['Velocidad'].transform(
            lambda x: x.rolling(window=window, min_periods=2).std().fillna(0)
        )
        
        # Aceleración promedio en ventana
        if 'Aceleracion' in df_features.columns:
            df_features[f'AceleracionMedia_{window}p'] = grouped['Aceleracion'].transform(
                lambda x: x.rolling(window=window, min_periods=1).mean()
            )
    
    print(f"✅ Features estadísticas calculadas")
    
    return df_features


def calculate_behavioral_features(df):
    """
    Calcula features de comportamiento del conductor
    
    Args:
        df: DataFrame con ubicaciones
    
    Returns:
        DataFrame con features de comportamiento
    """
    print("\n🚗 Calculando features de comportamiento...")
    
    df_features = df.copy()
    
    # Frenado brusco (aceleración negativa fuerte)
    if 'Aceleracion' in df_features.columns:
        df_features['FrenadoBrusco'] = (df_features['Aceleracion'] < -2.0).astype(int)
        
        # Aceleración brusca (aceleración positiva fuerte)
        df_features['AceleracionBrusca'] = (df_features['Aceleracion'] > 2.0).astype(int)
    
    # Exceso de velocidad (>80 km/h)
    df_features['ExcesoVelocidad'] = (df_features['Velocidad'] > 80).astype(int)
    
    # Giro brusco (cambio de dirección > 45° en poco tiempo)
    if 'CambioDireccion' in df_features.columns and 'TiempoDesdeAnterior' in df_features.columns:
        df_features['GiroBrusco'] = (
            (df_features['CambioDireccion'] > 45) & 
            (df_features['TiempoDesdeAnterior'] < 5)
        ).astype(int)
    
    # Contadores acumulados por dispositivo
    grouped = df_features.groupby('DispositivoID')
    
    if 'FrenadoBrusco' in df_features.columns:
        df_features['TotalFrenadosBruscos'] = grouped['FrenadoBrusco'].cumsum()
    
    if 'AceleracionBrusca' in df_features.columns:
        df_features['TotalAceleracionesBruscas'] = grouped['AceleracionBrusca'].cumsum()
    
    if 'ExcesoVelocidad' in df_features.columns:
        df_features['TotalExcesosVelocidad'] = grouped['ExcesoVelocidad'].cumsum()
    
    print(f"✅ Features de comportamiento calculadas")
    
    return df_features


def save_engineered_features(df, filename='features_engineered.csv'):
    """
    Guarda datos con features engineeradas
    
    Args:
        df: DataFrame con features
        filename: Nombre del archivo de salida
    """
    processed_dir = Path(DATA_DIR) / 'processed'
    processed_dir.mkdir(parents=True, exist_ok=True)
    
    # Agregar timestamp al nombre
    timestamp = datetime.now().strftime("%Y%m%d")
    output_path = processed_dir / f"{filename.replace('.csv', '')}_{timestamp}.csv"
    
    df.to_csv(output_path, index=False, encoding='utf-8')
    print(f"\n💾 Features guardadas en: {output_path}")
    print(f"   Total de columnas: {len(df.columns)}")
    
    return output_path


def feature_engineering_pipeline():
    """
    Pipeline completo de feature engineering
    """
    print("=" * 60)
    print("🚀 FEATURE ENGINEERING")
    print("=" * 60)
    
    # 1. Cargar datos procesados
    df = load_processed_data()
    if df is None:
        return False
    
    print(f"\n📊 Columnas iniciales: {len(df.columns)}")
    
    # 2. Features de trayectoria
    df = calculate_trip_features(df)
    
    # 3. Features estadísticas
    df = calculate_statistical_features(df)
    
    # 4. Features de comportamiento
    df = calculate_behavioral_features(df)
    
    # 5. Guardar dataset con features
    save_engineered_features(df)
    
    print(f"\n📊 Columnas finales: {len(df.columns)}")
    print(f"   Features nuevas agregadas: {len(df.columns) - 15}")  # Aproximado
    
    print("\n" + "=" * 60)
    print("✅ FEATURE ENGINEERING COMPLETADO")
    print("=" * 60)
    
    print("\n📝 Algunas features creadas:")
    new_features = [col for col in df.columns if any(x in col for x in [
        'Calculada', 'Aceleracion', 'Media', 'Std', 'Brusco', 'Acumulada'
    ])]
    for feat in new_features[:15]:  # Mostrar primeras 15
        print(f"  • {feat}")
    
    return True


if __name__ == "__main__":
    feature_engineering_pipeline()
