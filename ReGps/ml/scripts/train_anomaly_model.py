"""
Script de entrenamiento para el modelo de detección de anomalías
Usa la clase AnomalyDetector del módulo models
"""

import sys
from pathlib import Path
import pandas as pd
from datetime import datetime

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from models.anomaly_detector import AnomalyDetector
from config import RAW_DATA_DIR, MODELS_DIR
import joblib


def load_latest_data():
    """Carga los archivos CSV más recientes"""
    print("\n📂 Cargando datos de entrenamiento...")
    
    ubicaciones_files = list(RAW_DATA_DIR.glob("ubicaciones_raw_*.csv"))
    if not ubicaciones_files:
        raise FileNotFoundError("No se encontraron archivos de ubicaciones")
    
    latest_ubicaciones = max(ubicaciones_files, key=lambda p: p.stat().st_mtime)
    print(f"📄 Cargando: {latest_ubicaciones.name}")
    df_ubicaciones = pd.read_csv(latest_ubicaciones)
    
    print(f"✅ Cargadas {len(df_ubicaciones):,} ubicaciones")
    
    return df_ubicaciones


def main():
    """Función principal"""
    print("=" * 70)
    print("🚀 ENTRENAMIENTO DE MODELO DE DETECCIÓN DE ANOMALÍAS")
    print("=" * 70)
    
    # 1. Cargar datos
    df_ubicaciones = load_latest_data()
    
    # 2. Crear instancia del modelo
    detector = AnomalyDetector(
        contamination=0.15,
        n_estimators=100,
        random_state=42
    )
    
    # 3. Crear features
    X = detector.create_features(df_ubicaciones)
    
    # 4. Entrenar modelo
    metrics = detector.train(X, test_size=0.2)
    
    # 5. Guardar modelo
    model_path = MODELS_DIR / "trained" / "anomaly_detector.joblib"
    model_path.parent.mkdir(parents=True, exist_ok=True)
    detector.save(model_path)
    
    # 6. Guardar metadata
    metadata = {
        'model_name': 'anomaly_detector',
        'model_type': 'IsolationForest',
        'version': '1.0.0',
        'trained_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'n_samples': len(X),
        'n_features': len(detector.feature_columns),
        'feature_names': detector.feature_columns,
        'contamination': detector.contamination,
        'test_anomalies': metrics['test_anomalies'],
        'test_anomaly_rate': metrics['test_anomaly_rate']
    }
    
    metadata_path = MODELS_DIR / "metadata" / "anomaly_detector_metadata.joblib"
    metadata_path.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(metadata, metadata_path)
    print(f"✅ Metadata guardada: {metadata_path}")
    
    print("\n" + "=" * 70)
    print("✅ ENTRENAMIENTO COMPLETADO")
    print("=" * 70)
    print(f"\n📊 Resumen:")
    print(f"   • Modelo: IsolationForest")
    print(f"   • Features: {metadata['n_features']}")
    print(f"   • Contamination: {metadata['contamination']}")
    print(f"   • Tasa de anomalías en test: {metadata['test_anomaly_rate']:.2f}%")
    print(f"\n💾 Modelo guardado en: {model_path}")
    print(f"\n💡 Siguiente paso: Actualizar api/app.py para cargar este modelo")


if __name__ == "__main__":
    main()
