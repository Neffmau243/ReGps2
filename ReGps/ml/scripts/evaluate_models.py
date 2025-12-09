"""
Script para evaluar todos los modelos entrenados
Genera reportes comprehensivos de rendimiento y visualizaciones

Evalúa:
- ETA Predictor (Regresión)
- Anomaly Detector (Detección de outliers)
- Behavior Classifier (Clasificación)
"""

import sys
from pathlib import Path
import pandas as pd
import numpy as np
import joblib
from datetime import datetime
import json

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from config import MODELS_DIR, RAW_DATA_DIR


def load_model(model_name):
    """
    Carga un modelo entrenado
    
    Args:
        model_name: Nombre del modelo (sin extensión)
    
    Returns:
        Modelo, metadata, o None si no existe
    """
    model_path = MODELS_DIR / f"{model_name}.joblib"
    metadata_path = MODELS_DIR / "metadata" / f"{model_name}_metadata.joblib"
    
    if not model_path.exists():
        return None, None
    
    model_data = joblib.load(model_path)
    metadata = None
    
    if metadata_path.exists():
        metadata = joblib.load(metadata_path)
    
    return model_data, metadata


def evaluate_eta_predictor():
    """Evalúa el modelo de predicción de ETA"""
    print("\n" + "=" * 70)
    print("📊 EVALUANDO: ETA PREDICTOR")
    print("=" * 70)
    
    model_data, metadata = load_model("eta_predictor")
    
    if model_data is None:
        print("❌ Modelo no encontrado. Ejecutar train_eta_model.py primero.")
        return None
    
    print("✅ Modelo cargado exitosamente")
    
    # Información básica
    print(f"\n📋 Información del Modelo:")
    print(f"   • Tipo: {metadata.get('model_type', 'N/A')}")
    print(f"   • Versión: {metadata.get('version', 'N/A')}")
    print(f"   • Fecha de entrenamiento: {metadata.get('trained_date', 'N/A')}")
    print(f"   • Samples (train): {metadata.get('n_samples_train', 'N/A'):,}")
    print(f"   • Samples (test): {metadata.get('n_samples_test', 'N/A'):,}")
    print(f"   • Features: {metadata.get('n_features', 'N/A')}")
    
    # Métricas de rendimiento
    if 'metrics' in metadata:
        metrics = metadata['metrics']
        print(f"\n📈 Métricas de Rendimiento:")
        print(f"   • RMSE: {metrics.get('rmse', 'N/A'):.2f} minutos")
        print(f"   • MAE: {metrics.get('mae', 'N/A'):.2f} minutos")
        print(f"   • MAPE: {metrics.get('mape', 'N/A'):.2f}%")
        print(f"   • R² Score: {metrics.get('r2_score', 'N/A'):.4f}")
    
    # Feature names
    if 'feature_names' in metadata:
        print(f"\n🔧 Features utilizados:")
        for i, feature in enumerate(metadata['feature_names'][:10], 1):
            print(f"   {i}. {feature}")
        if len(metadata['feature_names']) > 10:
            print(f"   ... y {len(metadata['feature_names']) - 10} más")
    
    return metadata


def evaluate_anomaly_detector():
    """Evalúa el modelo de detección de anomalías"""
    print("\n" + "=" * 70)
    print("🚨 EVALUANDO: ANOMALY DETECTOR")
    print("=" * 70)
    
    model_data, metadata = load_model("anomaly_detector")
    
    if model_data is None:
        print("❌ Modelo no encontrado. Ejecutar train_anomaly_model.py primero.")
        return None
    
    print("✅ Modelo cargado exitosamente")
    
    # Información básica
    print(f"\n📋 Información del Modelo:")
    print(f"   • Tipo: {metadata.get('model_type', 'N/A')}")
    print(f"   • Versión: {metadata.get('version', 'N/A')}")
    print(f"   • Fecha de entrenamiento: {metadata.get('trained_date', 'N/A')}")
    print(f"   • Samples (train): {metadata.get('n_samples_train', 'N/A'):,}")
    print(f"   • Samples (test): {metadata.get('n_samples_test', 'N/A'):,}")
    print(f"   • Features: {metadata.get('n_features', 'N/A')}")
    
    # Configuración de anomalías
    print(f"\n⚙️ Configuración:")
    print(f"   • Contamination: {metadata.get('contamination', 'N/A')}")
    print(f"   • Anomalías detectadas en test: {metadata.get('test_anomalies', 'N/A')}")
    print(f"   • Tasa de anomalías: {metadata.get('test_anomaly_rate', 'N/A'):.2f}%")
    
    # Feature names
    if 'feature_names' in metadata:
        print(f"\n🔧 Features utilizados:")
        for i, feature in enumerate(metadata['feature_names'][:10], 1):
            print(f"   {i}. {feature}")
        if len(metadata['feature_names']) > 10:
            print(f"   ... y {len(metadata['feature_names']) - 10} más")
    
    return metadata


def evaluate_behavior_classifier():
    """Evalúa el modelo de clasificación de comportamiento"""
    print("\n" + "=" * 70)
    print("👤 EVALUANDO: BEHAVIOR CLASSIFIER")
    print("=" * 70)
    
    model_data, metadata = load_model("behavior_classifier")
    
    if model_data is None:
        print("❌ Modelo no encontrado. Ejecutar train_behavior_classifier.py primero.")
        return None
    
    print("✅ Modelo cargado exitosamente")
    
    # Información básica
    print(f"\n📋 Información del Modelo:")
    print(f"   • Tipo: {metadata.get('model_type', 'N/A')}")
    print(f"   • Versión: {metadata.get('version', 'N/A')}")
    print(f"   • Fecha de entrenamiento: {metadata.get('trained_date', 'N/A')}")
    print(f"   • Samples (train): {metadata.get('n_samples_train', 'N/A'):,}")
    print(f"   • Samples (test): {metadata.get('n_samples_test', 'N/A'):,}")
    print(f"   • Features: {metadata.get('n_features', 'N/A')}")
    print(f"   • Categorías: {', '.join(metadata.get('categories', []))}")
    
    # Métricas de rendimiento
    if 'metrics' in metadata:
        metrics = metadata['metrics']
        print(f"\n📈 Métricas de Rendimiento:")
        print(f"   • Accuracy: {metrics.get('accuracy', 'N/A'):.4f} ({metrics.get('accuracy', 0)*100:.2f}%)")
        
        # Classification report por categoría
        if 'classification_report' in metrics:
            report = metrics['classification_report']
            print(f"\n   Métricas por Categoría:")
            for category in ['eficiente', 'normal', 'requiere_atencion']:
                if category in report:
                    cat_metrics = report[category]
                    print(f"   • {category}:")
                    print(f"     - Precision: {cat_metrics.get('precision', 0):.3f}")
                    print(f"     - Recall: {cat_metrics.get('recall', 0):.3f}")
                    print(f"     - F1-Score: {cat_metrics.get('f1-score', 0):.3f}")
                    print(f"     - Support: {int(cat_metrics.get('support', 0))}")
    
    # Feature names
    if 'feature_names' in metadata:
        print(f"\n🔧 Features utilizados:")
        for i, feature in enumerate(metadata['feature_names'][:10], 1):
            print(f"   {i}. {feature}")
        if len(metadata['feature_names']) > 10:
            print(f"   ... y {len(metadata['feature_names']) - 10} más")
    
    return metadata


def generate_evaluation_report(eta_meta, anomaly_meta, behavior_meta):
    """
    Genera un reporte de evaluación comprehensivo
    
    Args:
        eta_meta: Metadata del modelo ETA
        anomaly_meta: Metadata del modelo de anomalías
        behavior_meta: Metadata del modelo de clasificación
    """
    print("\n" + "=" * 70)
    print("📝 GENERANDO REPORTE DE EVALUACIÓN")
    print("=" * 70)
    
    report = {
        'evaluation_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
        'models_evaluated': [],
        'summary': {}
    }
    
    # Agregar información de cada modelo
    if eta_meta:
        report['models_evaluated'].append('eta_predictor')
        report['eta_predictor'] = {
            'type': eta_meta.get('model_type'),
            'version': eta_meta.get('version'),
            'trained': eta_meta.get('trained_date'),
            'samples_train': eta_meta.get('n_samples_train'),
            'samples_test': eta_meta.get('n_samples_test'),
            'metrics': eta_meta.get('metrics', {})
        }
        report['summary']['eta_rmse'] = eta_meta.get('metrics', {}).get('rmse', 'N/A')
    
    if anomaly_meta:
        report['models_evaluated'].append('anomaly_detector')
        report['anomaly_detector'] = {
            'type': anomaly_meta.get('model_type'),
            'version': anomaly_meta.get('version'),
            'trained': anomaly_meta.get('trained_date'),
            'samples_train': anomaly_meta.get('n_samples_train'),
            'samples_test': anomaly_meta.get('n_samples_test'),
            'contamination': anomaly_meta.get('contamination'),
            'anomaly_rate': anomaly_meta.get('test_anomaly_rate')
        }
        report['summary']['anomaly_detection_rate'] = anomaly_meta.get('test_anomaly_rate', 'N/A')
    
    if behavior_meta:
        report['models_evaluated'].append('behavior_classifier')
        report['behavior_classifier'] = {
            'type': behavior_meta.get('model_type'),
            'version': behavior_meta.get('version'),
            'trained': behavior_meta.get('trained_date'),
            'samples_train': behavior_meta.get('n_samples_train'),
            'samples_test': behavior_meta.get('n_samples_test'),
            'categories': behavior_meta.get('categories', []),
            'accuracy': behavior_meta.get('metrics', {}).get('accuracy', 'N/A')
        }
        report['summary']['behavior_accuracy'] = behavior_meta.get('metrics', {}).get('accuracy', 'N/A')
    
    # Guardar reporte
    metadata_dir = MODELS_DIR / "metadata"
    metadata_dir.mkdir(parents=True, exist_ok=True)
    
    report_path = metadata_dir / "evaluation_report.json"
    with open(report_path, 'w', encoding='utf-8') as f:
        json.dump(report, f, indent=2, ensure_ascii=False)
    
    print(f"✅ Reporte guardado en: {report_path}")
    
    return report


def print_summary(report):
    """Imprime un resumen final de la evaluación"""
    print("\n" + "=" * 70)
    print("📊 RESUMEN DE EVALUACIÓN")
    print("=" * 70)
    
    print(f"\nModelos evaluados: {len(report['models_evaluated'])}")
    for model_name in report['models_evaluated']:
        print(f"   ✅ {model_name}")
    
    print(f"\nMétricas clave:")
    if 'eta_rmse' in report['summary']:
        print(f"   • ETA RMSE: {report['summary']['eta_rmse']:.2f} minutos")
    
    if 'anomaly_detection_rate' in report['summary']:
        print(f"   • Tasa de anomalías: {report['summary']['anomaly_detection_rate']:.2f}%")
    
    if 'behavior_accuracy' in report['summary']:
        acc = report['summary']['behavior_accuracy']
        print(f"   • Accuracy de clasificación: {acc:.4f} ({acc*100:.2f}%)")
    
    print(f"\n💡 Próximos pasos:")
    print(f"   1. Revisar métricas y decidir si reentrenar con más datos")
    print(f"   2. Integrar modelos en api/app.py")
    print(f"   3. Probar endpoints con modelos cargados")


def main():
    """Función principal"""
    print("=" * 70)
    print("🚀 EVALUACIÓN DE MODELOS ML - ReGPS")
    print("=" * 70)
    print(f"\nFecha: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print(f"Directorio de modelos: {MODELS_DIR}")
    
    # Evaluar cada modelo
    eta_meta = evaluate_eta_predictor()
    anomaly_meta = evaluate_anomaly_detector()
    behavior_meta = evaluate_behavior_classifier()
    
    # Verificar si hay al menos un modelo
    if not any([eta_meta, anomaly_meta, behavior_meta]):
        print("\n❌ No se encontraron modelos entrenados.")
        print("💡 Por favor ejecuta los scripts de entrenamiento primero:")
        print("   - train_eta_model.py")
        print("   - train_anomaly_model.py")
        print("   - train_behavior_classifier.py")
        return
    
    # Generar reporte
    report = generate_evaluation_report(eta_meta, anomaly_meta, behavior_meta)
    
    # Imprimir resumen
    print_summary(report)
    
    print("\n" + "=" * 70)
    print("✅ EVALUACIÓN COMPLETADA")
    print("=" * 70)


if __name__ == "__main__":
    main()
