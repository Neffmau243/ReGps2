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

# Configuración de Features
FEATURE_CONFIG = {
    "min_speed": 0,  # km/h
    "max_speed": 200,  # km/h
    "min_distance": 0.001,  # km (1 metro)
}

# API Configuration
API_CONFIG = {
    "host": "0.0.0.0",
    "port": 8001,
    "reload": True,  # Solo en desarrollo
}

# Logging
LOGGING_CONFIG = {
    "level": "INFO",
    "format": "%(asctime)s - %(name)s - %(levelname)s - %(message)s",
}

print(f"✅ Configuración cargada desde: {BASE_DIR}")
print(f"📊 Directorio de datos: {DATA_DIR}")
print(f"🤖 Directorio de modelos: {MODELS_DIR}")
