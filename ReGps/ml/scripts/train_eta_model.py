"""
Script de entrenamiento para el modelo de predicción de ETA
Usa la clase ETAPredictor del módulo models
"""

import sys
from pathlib import Path
import pandas as pd
from datetime import datetime

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from models.eta_predictor import ETAPredictor
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
    print("🚀 ENTRENAMIENTO DE MODELO DE PREDICCIÓN DE ETA")
    print("=" * 70)
    
    # 1. Cargar datos
    df_ubicaciones = load_latest_data()
    
    # 2. Crear instancia del modelo
    predictor = ETAPredictor(
        n_estimators=100,
        max_depth=20,
        random_state=42
    )
    
    # 3. Crear segmentos de ruta
    df_segments = predictor.create_route_segments(df_ubicaciones)
    
    if len(df_segments) < 10:
        print("⚠️ No hay suficientes segmentos de ruta para entrenar el modelo")
        print(f"   Se necesitan al menos 10 segmentos, solo se encontraron {len(df_segments)}")
        return
    
    # 4. Crear features
    X, y = predictor.create_features(df_segments, df_ubicaciones)
    
    # 5. Entrenar modelo
    metrics = predictor.train(X, y, test_size=0.2)
    
    # 6. Guardar modelo
    model_path = MODELS_DIR / "trained" / "eta_predictor.joblib"
    model_path.parent.mkdir(parents=True, exist_ok=True)
    predictor.save(model_path)
    
    # 7. Guardar metadata
    metadata = {
        'model_name': 'eta_predictor',
        'model_type': 'RandomForestRegressor',
        'version': '1.0.0',
        'trained_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'n_samples': len(df_segments),
        'n_features': len(predictor.feature_columns),
        'feature_names': predictor.feature_columns,
        'metrics': metrics
    }
    
    metadata_path = MODELS_DIR / "metadata" / "eta_predictor_metadata.joblib"
    metadata_path.parent.mkdir(parents=True, exist_ok=True)
    joblib.dump(metadata, metadata_path)
    print(f"✅ Metadata guardada: {metadata_path}")
    
    print("\n" + "=" * 70)
    print("✅ ENTRENAMIENTO COMPLETADO")
    print("=" * 70)
    print(f"\n📊 Resumen:")
    print(f"   • Modelo: RandomForestRegressor")
    print(f"   • Features: {metadata['n_features']}")
    print(f"   • RMSE: {metrics['rmse']:.2f} minutos")
    print(f"   • MAE: {metrics['mae']:.2f} minutos")
    print(f"   • R² Score: {metrics['r2_score']:.4f}")
    print(f"\n💾 Modelo guardado en: {model_path}")
    print(f"\n💡 Siguiente paso: Actualizar api/app.py para cargar este modelo")


if __name__ == "__main__":
    main()
