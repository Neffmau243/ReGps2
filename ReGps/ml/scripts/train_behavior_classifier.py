"""
Script de entrenamiento para el modelo de clasificación de comportamiento
Usa la clase BehaviorClassifier del módulo models
"""

import sys
from pathlib import Path
import pandas as pd
from datetime import datetime

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from models.behavior_classifier import BehaviorClassifier
from config import RAW_DATA_DIR, MODELS_DIR
import joblib


def load_latest_data():
    """Carga los archivos CSV más recientes"""
    print("\n📂 Cargando datos de entrenamiento...")
    
    # Ubicaciones
    ubicaciones_files = list(RAW_DATA_DIR.glob("ubicaciones_raw_*.csv"))
    if not ubicaciones_files:
        raise FileNotFoundError("No se encontraron archivos de ubicaciones")
    
    latest_ubicaciones = max(ubicaciones_files, key=lambda p: p.stat().st_mtime)
    print(f"📄 Cargando ubicaciones: {latest_ubicaciones.name}")
    df_ubicaciones = pd.read_csv(latest_ubicaciones)
    
    # Historial de zonas (opcional)
    historial_files = list(RAW_DATA_DIR.glob("historial_zonas_raw_*.csv"))
    df_historial = None
    if historial_files:
        latest_historial = max(historial_files, key=lambda p: p.stat().st_mtime)
        print(f"📄 Cargando historial zonas: {latest_historial.name}")
        df_historial = pd.read_csv(latest_historial)
    
    # Alertas (opcional)
    alertas_files = list(RAW_DATA_DIR.glob("alertas_raw_*.csv"))
    df_alertas = None
    if alertas_files:
        latest_alertas = max(alertas_files, key=lambda p: p.stat().st_mtime)
        print(f"📄 Cargando alertas: {latest_alertas.name}")
        df_alertas = pd.read_csv(latest_alertas)
    
    print(f"✅ Datos cargados:")
    print(f"   • Ubicaciones: {len(df_ubicaciones):,}")
    if df_historial is not None:
        print(f"   • Historial zonas: {len(df_historial):,}")
    if df_alertas is not None:
        print(f"   • Alertas: {len(df_alertas):,}")
    
    return df_ubicaciones, df_historial, df_alertas


def main():
    """Función principal"""
    print("=" * 70)
    print("🚀 ENTRENAMIENTO DE MODELO DE CLASIFICACIÓN DE COMPORTAMIENTO")
    print("=" * 70)
    
    # 1. Cargar datos
    df_ubicaciones, df_historial, df_alertas = load_latest_data()
    
    # 2. Crear instancia del modelo
    classifier = BehaviorClassifier(
        n_estimators=100,
        max_depth=15,
        random_state=42
    )
    
    # 3. Crear métricas diarias
    df_metrics = classifier.create_daily_metrics(df_ubicaciones, df_historial, df_alertas)
    
    if len(df_metrics) < 10:
        print("⚠️ No hay suficientes observaciones para entrenar el modelo")
        print(f"   Se necesitan al menos 10 observaciones, solo se encontraron {len(df_metrics)}")
        return
    
    # 4. Crear features
    X, y = classifier.create_features(df_metrics)
    
    # 5. Entrenar modelo
    metrics = classifier.train(X, y, test_size=0.2)
    
    # 6. Guardar modelo
    model_path = MODELS_DIR / "trained" / "behavior_classifier.joblib"
    model_path.parent.mkdir(parents=True, exist_ok=True)
    classifier.save(model_path)
    
    # 7. Guardar metadata
    metadata = {
        'model_name': 'behavior_classifier',
        'model_type': 'RandomForestClassifier',
        'version': '1.0.0',
        'trained_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'n_samples': len(df_metrics),
        'n_features': len(classifier.feature_columns),
        'feature_names': classifier.feature_columns,
        'categories': classifier.categories,
        'metrics': metrics
    }
    
    metadata_path = MODELS_DIR / "metadata" / "behavior_classifier_metadata.joblib"
    metadata_path.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(metadata, metadata_path)
    print(f"✅ Metadata guardada: {metadata_path}")
    
    print("\n" + "=" * 70)
    print("✅ ENTRENAMIENTO COMPLETADO")
    print("=" * 70)
    print(f"\n📊 Resumen:")
    print(f"   • Modelo: RandomForestClassifier")
    print(f"   • Features: {metadata['n_features']}")
    print(f"   • Accuracy: {metrics['accuracy']:.4f} ({metrics['accuracy']*100:.2f}%)")
    print(f"   • Categorías: {', '.join(classifier.categories)}")
    print(f"\n💾 Modelo guardado en: {model_path}")
    print(f"\n💡 Siguiente paso: Actualizar api/app.py para cargar este modelo")


if __name__ == "__main__":
    main()
