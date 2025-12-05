# Módulo de Machine Learning - ReGPS

Este directorio contiene toda la implementación de Machine Learning para el sistema ReGPS.

## 📁 Estructura

```
ml/
├── data/               # Datos para entrenamiento
│   ├── raw/           # Datos crudos de la BD
│   ├── processed/     # Datos procesados
│   └── cache/         # Cache temporal
├── models/            # Modelos entrenados (.joblib)
├── notebooks/         # Jupyter notebooks para análisis
├── scripts/           # Scripts de procesamiento y entrenamiento
├── api/               # API FastAPI para predicciones
├── utils/             # Utilidades (DB, geo, etc.)
└── tests/             # Tests unitarios
```

## 🚀 Inicio Rápido

### 1. Crear entorno virtual

```bash
python -m venv venv
.\venv\Scripts\activate  # Windows
source venv/bin/activate # Linux/Mac
```

### 2. Instalar dependencias

```bash
pip install -r requirements.txt
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env.ml
# Editar .env.ml con tus credenciales
```

### 4. Probar conexión a BD

```bash
python utils/db_connector.py
```

## 📊 Casos de Uso Implementados

- [ ] Predicción de ETA (Estimated Time of Arrival)
- [ ] Detección de Anomalías en rutas
- [ ] Clasificación de Comportamiento del Conductor
- [ ] Optimización de Rutas
- [ ] Predicción de Mantenimiento

## 📚 Documentación

Ver `ML_IMPLEMENTATION_GUIDE.md` en el directorio raíz del proyecto.

## 🧪 Testing

```bash
pytest tests/
```

## 🔗 Integración con Laravel

El módulo ML se integra con Laravel mediante:
1. Consultas directas a la base de datos (lectura)
2. API FastAPI para predicciones (puerto 8001)
3. Artisan commands para entrenar modelos

## 📝 Notas

- Los modelos entrenados se guardan en `models/`
- Los datos procesados se cachean en `data/cache/`
- Para desarrollo, usar Jupyter notebooks en `notebooks/`
