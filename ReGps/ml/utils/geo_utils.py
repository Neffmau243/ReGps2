"""
Utilidades para cálculos geoespaciales
"""

from geopy.distance import geodesic
import math
import numpy as np


def calculate_distance(lat1, lon1, lat2, lon2):
    """
    Calcular distancia entre dos puntos GPS en kilómetros
    
    Args:
        lat1, lon1: Coordenadas del punto 1
        lat2, lon2: Coordenadas del punto 2
    
    Returns:
        float: Distancia en kilómetros
    """
    punto1 = (lat1, lon1)
    punto2 = (lat2, lon2)
    return geodesic(punto1, punto2).kilometers


def calculate_bearing(lat1, lon1, lat2, lon2):
    """
    Calcular el rumbo (bearing) entre dos puntos en grados
    
    Returns:
        float: Ángulo en grados (0-360)
    """
    lat1_rad = math.radians(lat1)
    lat2_rad = math.radians(lat2)
    diff_lon_rad = math.radians(lon2 - lon1)
    
    x = math.sin(diff_lon_rad) * math.cos(lat2_rad)
    y = math.cos(lat1_rad) * math.sin(lat2_rad) - (
        math.sin(lat1_rad) * math.cos(lat2_rad) * math.cos(diff_lon_rad)
    )
    
    bearing_rad = math.atan2(x, y)
    bearing_deg = math.degrees(bearing_rad)
    
    return (bearing_deg + 360) % 360


def calculate_speed(lat1, lon1, time1, lat2, lon2, time2):
    """
    Calcular velocidad entre dos puntos GPS
    
    Args:
        lat1, lon1, time1: Coordenadas y timestamp del punto 1
        lat2, lon2, time2: Coordenadas y timestamp del punto 2
    
    Returns:
        float: Velocidad en km/h
    """
    distance_km = calculate_distance(lat1, lon1, lat2, lon2)
    time_diff_hours = (time2 - time1).total_seconds() / 3600
    
    if time_diff_hours == 0:
        return 0
    
    return distance_km / time_diff_hours


def is_point_in_circle(point_lat, point_lon, center_lat, center_lon, radius_km):
    """
    Verificar si un punto está dentro de un círculo (geocerca circular)
    
    Args:
        point_lat, point_lon: Coordenadas del punto a verificar
        center_lat, center_lon: Centro del círculo
        radius_km: Radio en kilómetros
    
    Returns:
        bool: True si está dentro, False si está fuera
    """
    distance = calculate_distance(point_lat, point_lon, center_lat, center_lon)
    return distance <= radius_km


def calculate_acceleration(speed1, speed2, time_diff_seconds):
    """
    Calcular aceleración entre dos puntos
    
    Args:
        speed1, speed2: Velocidades en km/h
        time_diff_seconds: Diferencia de tiempo en segundos
    
    Returns:
        float: Aceleración en m/s²
    """
    if time_diff_seconds == 0:
        return 0
    
    # Convertir km/h a m/s
    speed1_ms = speed1 * 1000 / 3600
    speed2_ms = speed2 * 1000 / 3600
    
    # Aceleración = (velocidad_final - velocidad_inicial) / tiempo
    acceleration = (speed2_ms - speed1_ms) / time_diff_seconds
    
    return acceleration


def haversine_distance_batch(lat1, lon1, lat2, lon2):
    """
    Calcular distancias para múltiples pares de coordenadas (vectorizado)
    Útil para DataFrames de pandas
    
    Args:
        lat1, lon1, lat2, lon2: Arrays de numpy o Series de pandas
    
    Returns:
        numpy.ndarray: Distancias en kilómetros
    """
    # Radio de la Tierra en km
    R = 6371.0
    
    # Convertir a radianes
    lat1_rad = np.radians(lat1)
    lon1_rad = np.radians(lon1)
    lat2_rad = np.radians(lat2)
    lon2_rad = np.radians(lon2)
    
    # Diferencias
    dlat = lat2_rad - lat1_rad
    dlon = lon2_rad - lon1_rad
    
    # Fórmula de Haversine
    a = np.sin(dlat / 2)**2 + np.cos(lat1_rad) * np.cos(lat2_rad) * np.sin(dlon / 2)**2
    c = 2 * np.arctan2(np.sqrt(a), np.sqrt(1 - a))
    
    distance = R * c
    
    return distance


# Ejemplo de uso
if __name__ == "__main__":
    # Ejemplo: Distancia entre dos puntos en Ciudad de México
    lat1, lon1 = 19.4326, -99.1332  # Zócalo
    lat2, lon2 = 19.4284, -99.1277  # Palacio de Bellas Artes
    
    distancia = calculate_distance(lat1, lon1, lat2, lon2)
    print(f"📍 Distancia: {distancia:.3f} km")
    
    bearing = calculate_bearing(lat1, lon1, lat2, lon2)
    print(f"🧭 Rumbo: {bearing:.1f}°")
    
    # Verificar si un punto está en un radio de 1 km
    punto_test = (19.4300, -99.1300)
    dentro = is_point_in_circle(punto_test[0], punto_test[1], lat1, lon1, 1.0)
    print(f"🎯 ¿Punto dentro del círculo?: {dentro}")
