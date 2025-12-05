"""
Script de prueba rápida del módulo ML
Verifica que todas las librerías estén instaladas correctamente
"""

print("🧪 Probando instalación de librerías ML...")
print("-" * 50)

# Core ML
try:
    import numpy as np
    print("✅ NumPy:", np.__version__)
except ImportError as e:
    print("❌ NumPy:", e)

try:
    import pandas as pd
    print("✅ Pandas:", pd.__version__)
except ImportError as e:
    print("❌ Pandas:", e)

try:
    import sklearn
    print("✅ scikit-learn:", sklearn.__version__)
except ImportError as e:
    print("❌ scikit-learn:", e)

try:
    import joblib
    print("✅ Joblib:", joblib.__version__)
except ImportError as e:
    print("❌ Joblib:", e)

# Geoespacial
try:
    import geopy
    print("✅ Geopy:", geopy.__version__)
except ImportError as e:
    print("❌ Geopy:", e)

try:
    import shapely
    print("✅ Shapely:", shapely.__version__)
except ImportError as e:
    print("❌ Shapely:", e)

# API
try:
    import fastapi
    print("✅ FastAPI:", fastapi.__version__)
except ImportError as e:
    print("❌ FastAPI:", e)

try:
    import pymysql
    print("✅ PyMySQL:", pymysql.__version__)
except ImportError as e:
    print("❌ PyMySQL:", e)

# Visualización (opcional)
try:
    import matplotlib
    print("✅ Matplotlib:", matplotlib.__version__)
except ImportError as e:
    print("⚠️  Matplotlib: No instalado (opcional)")

try:
    import seaborn
    print("✅ Seaborn:", seaborn.__version__)
except ImportError as e:
    print("⚠️  Seaborn: No instalado (opcional)")

# Jupyter (opcional)
try:
    import jupyter
    print("✅ Jupyter instalado")
except ImportError as e:
    print("⚠️  Jupyter: No instalado (opcional)")

print("-" * 50)
print("\n🎉 Verificación completada!")
print("\n💡 Próximos pasos:")
print("1. Configurar .env.ml con credenciales de BD")
print("2. Probar conexión: python utils/db_connector.py")
print("3. Extraer datos: python scripts/extract_data.py")
