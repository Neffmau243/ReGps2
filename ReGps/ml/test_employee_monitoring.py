"""
Test de endpoints ML - Contexto: Supervisión de Empleados con GPS
"""

import requests
import json

API_URL = "http://localhost:8001"

print("="*80)
print("🏢 TEST ML API - SUPERVISIÓN DE EMPLEADOS")
print("="*80)

# TEST 1: Detección de comportamiento normal
print("\n1️⃣ Empleado en ruta normal (sin alertas)")
print("-" * 80)
payload = {
    "dispositivo_id": 2,
    "ubicaciones": [
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 45.0},
        {"latitud": -16.382000, "longitud": -71.515500, "velocidad": 50.0},
        {"latitud": -16.383000, "longitud": -71.516000, "velocidad": 48.0},
        {"latitud": -16.384000, "longitud": -71.516500, "velocidad": 52.0}
    ]
}

response = requests.post(f"{API_URL}/detect/anomaly", json=payload)
data = response.json()
print(f"✅ Anomalía: {data['es_anomalia']}")
print(f"   Score: {data['score_anomalia']:.3f}")
print(f"   Detalles: {data['detalles']}")

# TEST 2: Exceso de velocidad (alerta de seguridad)
print("\n2️⃣ Empleado excediendo velocidad permitida (⚠️ ALERTA)")
print("-" * 80)
payload = {
    "dispositivo_id": 2,
    "ubicaciones": [
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 50.0},
        {"latitud": -16.382000, "longitud": -71.515500, "velocidad": 95.0},  # ⚠️
        {"latitud": -16.383000, "longitud": -71.516000, "velocidad": 100.0},  # ⚠️
        {"latitud": -16.384000, "longitud": -71.516500, "velocidad": 92.0}   # ⚠️
    ]
}

response = requests.post(f"{API_URL}/detect/anomaly", json=payload)
data = response.json()
print(f"⚠️ Anomalía detectada: {data['es_anomalia']}")
print(f"   Tipo: {data['tipo_anomalia']}")
print(f"   Score: {data['score_anomalia']:.3f}")
print(f"   Detalles: {data['detalles']}")
print("\n   💡 Acción sugerida:")
print("      → Crear alerta para supervisor")
print("      → Notificar al empleado sobre límite de velocidad")

# TEST 3: Parada prolongada no autorizada
print("\n3️⃣ Empleado detenido por tiempo prolongado (⚠️ ALERTA)")
print("-" * 80)
payload = {
    "dispositivo_id": 2,
    "ubicaciones": [
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 45.0},
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 0.0},
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 0.0},
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 0.0},
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 0.0},
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 0.0},
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 0.0},
        {"latitud": -16.381696, "longitud": -71.515050, "velocidad": 2.0}
    ]
}

response = requests.post(f"{API_URL}/detect/anomaly", json=payload)
data = response.json()
print(f"⚠️ Anomalía detectada: {data['es_anomalia']}")
if data['tipo_anomalia']:
    print(f"   Tipo: {data['tipo_anomalia']}")
print(f"   Detalles: {data['detalles']}")
print("\n   💡 Acción sugerida:")
print("      → Verificar si es parada autorizada")
print("      → Contactar al empleado para confirmar status")

# TEST 4: Clasificación - Empleado eficiente
print("\n4️⃣ Clasificación de comportamiento - Empleado EFICIENTE")
print("-" * 80)
ubicaciones = []
for i in range(15):
    ubicaciones.append({
        "latitud": -16.381696 + (i * 0.0001),
        "longitud": -71.515050 + (i * 0.0001),
        "velocidad": 45.0 + (i * 0.5)
    })

payload = {
    "dispositivo_id": 2,
    "empleado_id": 5,
    "ubicaciones": ubicaciones
}

response = requests.post(f"{API_URL}/classify/behavior", json=payload)
data = response.json()
print(f"✅ Categoría: {data['categoria'].upper()}")
print(f"   Score: {data['score']:.1f}/100")
print(f"   Alertas: {data['alertas'] if data['alertas'] else 'Ninguna'}")
print(f"   Recomendaciones: {data['recomendaciones']}")
print("\n   📊 Métricas:")
for key, value in data['metricas'].items():
    print(f"      • {key}: {value}")

# TEST 5: Clasificación - Empleado que requiere atención
print("\n5️⃣ Clasificación de comportamiento - REQUIERE ATENCIÓN")
print("-" * 80)
ubicaciones = []
for i in range(15):
    ubicaciones.append({
        "latitud": -16.381696 + (i * 0.0001),
        "longitud": -71.515050 + (i * 0.0001),
        "velocidad": 75.0 + (i * 2.5)  # Velocidades altas
    })

payload = {
    "dispositivo_id": 2,
    "empleado_id": 3,
    "ubicaciones": ubicaciones
}

response = requests.post(f"{API_URL}/classify/behavior", json=payload)
data = response.json()
print(f"⚠️ Categoría: {data['categoria'].upper()}")
print(f"   Score: {data['score']:.1f}/100")
print(f"   Alertas: {', '.join(data['alertas']) if data['alertas'] else 'Ninguna'}")
print(f"   Recomendaciones: {data['recomendaciones']}")
print("\n   📊 Métricas:")
for key, value in data['metricas'].items():
    print(f"      • {key}: {value}")
print("\n   💡 Acción sugerida:")
print("      → Programar reunión con supervisor")
print("      → Revisar políticas de seguridad con el empleado")

# TEST 6: Predicción ETA para supervisor
print("\n6️⃣ Predicción ETA - ¿Cuándo llegará el empleado al punto de trabajo?")
print("-" * 80)
payload = {
    "dispositivo_id": 2,
    "ubicacion_actual": {
        "latitud": -16.381696,
        "longitud": -71.515050,
        "velocidad": 50.0
    },
    "destino": {
        "latitud": -16.420000,  # Punto de trabajo
        "longitud": -71.540000,
        "velocidad": 0
    },
    "hora_actual": 8,  # 8 AM - hora de inicio
    "dia_semana": 1  # Lunes
}

response = requests.post(f"{API_URL}/predict/eta", json=payload)
data = response.json()
print(f"✅ Tiempo estimado de llegada: {data['eta_minutos']:.1f} minutos")
print(f"   Distancia al destino: {data['distancia_km']:.2f} km")
print(f"   Velocidad esperada: {data['velocidad_promedio_esperada']:.0f} km/h")
print(f"   Confianza: {data['confianza']*100:.0f}%")
print("\n   💡 Uso en dashboard:")
print("      → Mostrar en mapa: 'Empleado llegará en 10 min'")
print("      → Notificar al cliente/supervisor del horario estimado")

print("\n" + "="*80)
print("✅ TESTS COMPLETADOS")
print("="*80)
print("\n📝 RESUMEN:")
print("   • API ajustada para contexto empresarial")
print("   • Detección de anomalías: exceso velocidad, paradas, comportamiento errático")
print("   • Clasificación: eficiente / normal / requiere_atención")
print("   • Métricas útiles para supervisores")
print("\n🎯 SIGUIENTE PASO:")
print("   Integrar con Laravel para alertas automáticas")
print("="*80)
