"""
FastAPI application para servir predicciones de ML
Sistema de Supervisión de Empleados con GPS - ReGPS

Endpoints:
- GET  /health              - Health check
- POST /predict/eta         - Predecir tiempo de llegada a destino
- POST /detect/anomaly      - Detectar comportamiento anómalo (desvíos, paradas, velocidad)
- POST /classify/behavior   - Clasificar comportamiento del empleado en ruta
- POST /verify/geofence     - Verificar si empleado está en zona permitida

Uso:
    uvicorn api.app:app --reload --port 8001
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import List, Optional
from datetime import datetime
from contextlib import asynccontextmanager
import sys
from pathlib import Path

# ============================================================================
# INICIALIZACIÓN
# ============================================================================

@asynccontextmanager
async def lifespan(app: FastAPI):
    """
    Eventos del ciclo de vida de la aplicación
    """
    # Startup
    print("🚀 ReGPS ML API iniciada")
    print("📊 Cargando modelos...")
    # TODO: Cargar modelos desde archivos .joblib
    print("✅ API lista para recibir requests")
    
    yield
    
    # Shutdown
    print("🛑 ReGPS ML API detenida")

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

# Configuración de la app
app = FastAPI(
    title="ReGPS ML API",
    description="API de predicciones de Machine Learning para ReGPS",
    version="1.0.0",
    lifespan=lifespan
)

# Configurar CORS para permitir requests desde Laravel
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000", "http://127.0.0.1:8000"],  # Laravel dev server
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ============================================================================
# MODELOS DE DATOS (Pydantic)
# ============================================================================

class LocationPoint(BaseModel):
    """Punto de ubicación GPS"""
    latitud: float = Field(..., ge=-90, le=90, description="Latitud (-90 a 90)")
    longitud: float = Field(..., ge=-180, le=180, description="Longitud (-180 a 180)")
    velocidad: Optional[float] = Field(0, ge=0, description="Velocidad en km/h")
    fecha_hora: Optional[datetime] = Field(None, description="Timestamp del punto")


class ETAPredictionRequest(BaseModel):
    """Request para predicción de ETA"""
    dispositivo_id: int = Field(..., description="ID del dispositivo")
    ubicacion_actual: LocationPoint = Field(..., description="Ubicación actual")
    destino: LocationPoint = Field(..., description="Ubicación de destino")
    hora_actual: Optional[int] = Field(None, ge=0, le=23, description="Hora del día (0-23)")
    dia_semana: Optional[int] = Field(None, ge=0, le=6, description="Día de la semana (0=Lunes)")


class ETAPredictionResponse(BaseModel):
    """Response de predicción de ETA"""
    eta_minutos: float = Field(..., description="Tiempo estimado de llegada en minutos")
    distancia_km: float = Field(..., description="Distancia estimada en kilómetros")
    velocidad_promedio_esperada: float = Field(..., description="Velocidad promedio esperada en km/h")
    confianza: float = Field(..., ge=0, le=1, description="Nivel de confianza (0-1)")
    timestamp: datetime = Field(default_factory=datetime.now)


class AnomalyDetectionRequest(BaseModel):
    """Request para detección de anomalías"""
    dispositivo_id: int = Field(..., description="ID del dispositivo")
    ubicaciones: List[LocationPoint] = Field(..., min_length=2, description="Lista de ubicaciones recientes")


class AnomalyDetectionResponse(BaseModel):
    """Response de detección de anomalías"""
    es_anomalia: bool = Field(..., description="¿Es una anomalía?")
    tipo_anomalia: Optional[str] = Field(None, description="Tipo de anomalía detectada")
    score_anomalia: float = Field(..., description="Score de anomalía (mayor = más anómalo)")
    detalles: Optional[str] = Field(None, description="Detalles adicionales")
    timestamp: datetime = Field(default_factory=datetime.now)


class BehaviorClassificationRequest(BaseModel):
    """Request para clasificación de comportamiento del empleado"""
    dispositivo_id: int = Field(..., description="ID del dispositivo")
    empleado_id: Optional[int] = Field(None, description="ID del empleado")
    ubicaciones: List[LocationPoint] = Field(..., min_length=10, description="Historial de ubicaciones")


class BehaviorClassificationResponse(BaseModel):
    """Response de clasificación de comportamiento"""
    categoria: str = Field(..., description="Categoría (eficiente, normal, requiere_atencion)")
    score: float = Field(..., ge=0, le=100, description="Score de comportamiento (0-100)")
    alertas: List[str] = Field(default_factory=list, description="Lista de alertas detectadas")
    metricas: dict = Field(..., description="Métricas calculadas")
    recomendaciones: Optional[str] = Field(None, description="Recomendaciones para el supervisor")
    timestamp: datetime = Field(default_factory=datetime.now)


# ============================================================================
# ENDPOINTS
# ============================================================================

@app.get("/")
async def root():
    """Endpoint raíz"""
    return {
        "message": "ReGPS ML API",
        "version": "1.0.0",
        "status": "online",
        "endpoints": {
            "health": "/health",
            "docs": "/docs",
            "predict_eta": "/predict/eta",
            "detect_anomaly": "/detect/anomaly",
            "classify_behavior": "/classify/behavior"
        }
    }


@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "timestamp": datetime.now(),
        "models_loaded": False,  # TODO: Actualizar cuando se carguen modelos
        "database_connected": False  # TODO: Verificar conexión a BD
    }


@app.post("/predict/eta", response_model=ETAPredictionResponse)
async def predict_eta(request: ETAPredictionRequest):
    """
    Predice el tiempo estimado de llegada (ETA)
    
    TODO: Implementar lógica de predicción con modelo entrenado
    """
    # Por ahora, retornar valores de ejemplo
    # Calcular distancia simple (línea recta)
    from utils.geo_utils import calculate_distance
    
    distancia = calculate_distance(
        request.ubicacion_actual.latitud,
        request.ubicacion_actual.longitud,
        request.destino.latitud,
        request.destino.longitud
    )
    
    # Estimar velocidad promedio (simplificado)
    velocidad_promedio = 40  # km/h promedio
    eta_horas = distancia / velocidad_promedio
    eta_minutos = eta_horas * 60
    
    return ETAPredictionResponse(
        eta_minutos=eta_minutos,
        distancia_km=distancia,
        velocidad_promedio_esperada=velocidad_promedio,
        confianza=0.75  # Placeholder
    )


@app.post("/detect/anomaly", response_model=AnomalyDetectionResponse)
async def detect_anomaly(request: AnomalyDetectionRequest):
    """
    Detecta anomalías en el comportamiento del empleado en ruta
    
    Detecta:
    - Exceso de velocidad (riesgo de seguridad)
    - Paradas no autorizadas prolongadas
    - Desvíos de ruta esperada
    
    TODO: Implementar lógica de detección con modelo entrenado
    """
    velocidades = [loc.velocidad for loc in request.ubicaciones if loc.velocidad is not None]
    
    if not velocidades:
        raise HTTPException(status_code=400, detail="No hay datos de velocidad")
    
    velocidad_max = max(velocidades)
    velocidad_promedio = sum(velocidades) / len(velocidades)
    
    # Detectar tipo de anomalía
    anomalias_detectadas = []
    tipo_anomalia = None
    
    # 1. Exceso de velocidad (umbral empresarial: 90 km/h)
    if velocidad_max > 90:
        anomalias_detectadas.append(f"Velocidad excesiva: {velocidad_max:.1f} km/h")
        tipo_anomalia = "exceso_velocidad"
    
    # 2. Parada prolongada (velocidad 0 por mucho tiempo)
    paradas = sum(1 for v in velocidades if v < 5)
    if paradas > len(velocidades) * 0.5:  # Más del 50% detenido
        anomalias_detectadas.append(f"Parada prolongada: {paradas}/{len(velocidades)} puntos")
        tipo_anomalia = "parada_prolongada" if not tipo_anomalia else tipo_anomalia
    
    # 3. Comportamiento errático (cambios bruscos de velocidad)
    if len(velocidades) > 1:
        cambios_velocidad = [abs(velocidades[i] - velocidades[i-1]) for i in range(1, len(velocidades))]
        cambios_bruscos = sum(1 for cambio in cambios_velocidad if cambio > 30)
        if cambios_bruscos > 3:
            anomalias_detectadas.append(f"Comportamiento errático: {cambios_bruscos} cambios bruscos")
            tipo_anomalia = "comportamiento_erratico" if not tipo_anomalia else tipo_anomalia
    
    es_anomalia = len(anomalias_detectadas) > 0
    score = velocidad_max / 150  # Normalizado para velocidades hasta 150 km/h
    
    detalles = " | ".join(anomalias_detectadas) if anomalias_detectadas else "Comportamiento normal detectado"
    
    return AnomalyDetectionResponse(
        es_anomalia=es_anomalia,
        tipo_anomalia=tipo_anomalia,
        score_anomalia=score,
        detalles=detalles
    )


@app.post("/classify/behavior", response_model=BehaviorClassificationResponse)
async def classify_behavior(request: BehaviorClassificationRequest):
    """
    Clasifica el comportamiento del empleado durante su jornada
    
    Evalúa:
    - Cumplimiento de velocidad segura
    - Eficiencia en movimiento
    - Paradas apropiadas
    - Comportamiento general en ruta
    
    TODO: Implementar lógica de clasificación con modelo entrenado
    """
    velocidades = [loc.velocidad for loc in request.ubicaciones if loc.velocidad is not None]
    
    if not velocidades:
        raise HTTPException(status_code=400, detail="No hay datos de velocidad")
    
    velocidad_promedio = sum(velocidades) / len(velocidades)
    velocidad_max = max(velocidades)
    paradas = sum(1 for v in velocidades if v < 5)
    tiempo_movimiento = len(velocidades) - paradas
    
    # Clasificación basada en métricas empresariales
    alertas = []
    recomendaciones = None
    
    # Evaluar velocidad
    if velocidad_max > 90:
        alertas.append("Exceso de velocidad detectado")
        categoria = "requiere_atencion"
        score = 45
        recomendaciones = "Recordar al empleado los límites de velocidad de la empresa"
    elif velocidad_promedio > 60:
        categoria = "normal"
        score = 70
    else:
        categoria = "eficiente"
        score = 90
    
    # Evaluar paradas
    porcentaje_paradas = (paradas / len(velocidades)) * 100
    if porcentaje_paradas > 60:
        alertas.append(f"Tiempo excesivo detenido: {porcentaje_paradas:.1f}%")
        if categoria == "eficiente":
            categoria = "normal"
            score = 65
    
    # Evaluar eficiencia
    if tiempo_movimiento > 0 and paradas / tiempo_movimiento < 0.3:
        # Buen ratio de movimiento
        if not alertas:
            score = min(score + 5, 100)
    
    if not alertas:
        recomendaciones = "Comportamiento dentro de parámetros normales"
    
    return BehaviorClassificationResponse(
        categoria=categoria,
        score=score,
        alertas=alertas,
        metricas={
            "velocidad_promedio": round(velocidad_promedio, 2),
            "velocidad_maxima": round(velocidad_max, 2),
            "puntos_analizados": len(request.ubicaciones),
            "tiempo_movimiento": tiempo_movimiento,
            "tiempo_detenido": paradas,
            "porcentaje_movimiento": round((tiempo_movimiento/len(velocidades))*100, 1)
        },
        recomendaciones=recomendaciones
    )


# ============================================================================
# EJECUTAR
# ============================================================================

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("api.app:app", host="0.0.0.0", port=8001, reload=True)
