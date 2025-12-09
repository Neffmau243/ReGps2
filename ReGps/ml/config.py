"""
Configuración del Módulo de Machine Learning
ReGPS - Sistema de Rastreo GPS
"""

import os
from pathlib import Path
from dotenv import load_dotenv

# Cargar variables de entorno
load_dotenv()

# Rutas base
BASE_DIR = Path(__file__).parent
DATA_DIR = BASE_DIR / "data"
RAW_DATA_DIR = DATA_DIR / "raw"
PROCESSED_DATA_DIR = DATA_DIR / "processed"
CACHE_DIR = DATA_DIR / "cache"
MODELS_DIR = BASE_DIR / "models"
NOTEBOOKS_DIR = BASE_DIR / "notebooks"

# Crear directorios si no existen
for directory in [RAW_DATA_DIR, PROCESSED_DATA_DIR, CACHE_DIR, MODELS_DIR]:
    directory.mkdir(parents=True, exist_ok=True)

# Configuración de Base de Datos Laravel
DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", 3306)),
    "user": os.getenv("DB_USERNAME", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "database": os.getenv("DB_DATABASE", "regps"),
    "charset": "utf8mb4"
}

# Configuración de Modelos ML
MODEL_CONFIG = {
    "test_size": 0.2,  # 20% para testing
    "random_state": 42,
    "cv_folds": 5,  # Cross-validation folds
}

# Parámetros específicos por modelo
MODEL_PARAMS = {
    "eta": {
        "n_estimators": 100,
        "max_depth": 20,
        "min_samples_split": 5,
        "min_samples_leaf": 2,
    },
    "anomaly": {
        "contamination": 0.15,
        "n_estimators": 100,
        "max_samples": 'auto',
    },
    "behavior": {
        "n_estimators": 100,
        "max_depth": 15,
        "min_samples_split": 5,
        "min_samples_leaf": 2,
        "class_weight": "balanced",
    }
}

# Umbrales de comportamiento y scoring
BEHAVIOR_SCORE_THRESHOLDS = {
    "speed_violation": -20,  # Penalización por exceso de velocidad
    "restricted_zone": -30,  # Penalización por zona restringida
    "missed_checkpoint": -15,  # Penalización por checkpoint no visitado
    "unauthorized_stop": -10,  # Penalización por parada no autorizada
    "checkpoint_bonus": 5,  # Bonus por visitar checkpoint
}

BEHAVIOR_CATEGORIES = {
    "eficiente": (90, 100),  # Score entre 90-100
    "normal": (60, 89),  # Score entre 60-89
    "requiere_atencion": (0, 59),  # Score entre 0-59
}

# Configuración de Features
FEATURE_CONFIG = {
    "min_speed": 0,  # km/h
    "max_speed": 200,  # km/h
    "min_distance": 0.001,  # km (1 metro)
    "speed_limit": 90,  # km/h - Límite empresarial
    "stop_threshold": 5,  # km/h - Velocidad para considerar detenido
    "erratic_change": 30,  # km/h - Cambio brusco de velocidad
}

print(f"✅ Configuración cargada desde: {BASE_DIR}")
print(f"📊 Directorio de datos: {DATA_DIR}")
print(f"🤖 Directorio de modelos: {MODELS_DIR}")
