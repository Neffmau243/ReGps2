"""
Modelo de Predicción de ETA (Estimated Time of Arrival)
Define la arquitectura y lógica del modelo de regresión para predecir tiempos de viaje
"""

import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
import joblib
from pathlib import Path
from datetime import datetime

import sys
sys.path.insert(0, str(Path(__file__).parent.parent))

from utils.geo_utils import calculate_distance, calculate_bearing
from utils.metrics import mae, rmse


class ETAPredictor:
    """
    Modelo de predicción de tiempo estimado de llegada
    Utiliza RandomForestRegressor para predecir tiempos de viaje
    """
    
    def __init__(self, n_estimators=100, max_depth=20, random_state=42):
        """
        Inicializa el predictor de ETA
        
        Args:
            n_estimators: Número de árboles en el bosque
            max_depth: Profundidad máxima de los árboles
            random_state: Semilla para reproducibilidad
        """
        self.model = RandomForestRegressor(
            n_estimators=n_estimators,
            max_depth=max_depth,
            min_samples_split=5,
            min_samples_leaf=2,
            random_state=random_state,
            n_jobs=-1
        )
        self.scaler = StandardScaler()
        self.feature_columns = None
        self.is_trained = False
        
    def create_route_segments(self, df_ubicaciones):
        """
        Crea segmentos de ruta con origen-destino y tiempo real de viaje
        
        Args:
            df_ubicaciones: DataFrame con ubicaciones
        
        Returns:
            DataFrame con segmentos de ruta y tiempo de viaje real
        """
        print("🛣️ Creando segmentos de ruta...")
        
        df_ubicaciones['FechaHora'] = pd.to_datetime(df_ubicaciones['FechaHora'])
        segments = []
        
        for dispositivo_id in df_ubicaciones['DispositivoID'].unique():
            device_locs = df_ubicaciones[df_ubicaciones['DispositivoID'] == dispositivo_id].copy()
            device_locs = device_locs.sort_values('FechaHora').reset_index(drop=True)
            
            for i in range(len(device_locs) - 1):
                origen = device_locs.iloc[i]
                destino = device_locs.iloc[i + 1]
                
                tiempo_viaje = (destino['FechaHora'] - origen['FechaHora']).total_seconds() / 60
                
                if tiempo_viaje < 0.5 or tiempo_viaje > 120:
                    continue
                
                distancia = calculate_distance(
                    origen['Latitud'], origen['Longitud'],
                    destino['Latitud'], destino['Longitud']
                )
                
                if distancia < 0.01:
                    continue
                
                bearing = calculate_bearing(
                    origen['Latitud'], origen['Longitud'],
                    destino['Latitud'], destino['Longitud']
                )
                
                segment = {
                    'DispositivoID': dispositivo_id,
                    'lat_origen': origen['Latitud'],
                    'lon_origen': origen['Longitud'],
                    'lat_destino': destino['Latitud'],
                    'lon_destino': destino['Longitud'],
                    'velocidad_origen': origen['Velocidad'],
                    'hora_inicio': origen['FechaHora'].hour,
                    'dia_semana': origen['FechaHora'].dayofweek,
                    'es_fin_semana': 1 if origen['FechaHora'].dayofweek >= 5 else 0,
                    'distancia_km': distancia,
                    'bearing': bearing,
                    'tiempo_viaje_min': tiempo_viaje,
                }
                
                segments.append(segment)
        
        df_segments = pd.DataFrame(segments)
        print(f"✅ Creados {len(df_segments):,} segmentos de ruta")
        
        return df_segments
    
    def create_features(self, df_segments, df_ubicaciones):
        """
        Crea features para el modelo
        
        Args:
            df_segments: DataFrame con segmentos de ruta
            df_ubicaciones: DataFrame con ubicaciones para estadísticas
        
        Returns:
            X (features), y (target)
        """
        print("🔧 Creando features para predicción de ETA...")
        
        device_avg_speed = df_ubicaciones.groupby('DispositivoID')['Velocidad'].mean().to_dict()
        df_segments['velocidad_promedio_historica'] = df_segments['DispositivoID'].map(device_avg_speed)
        
        def get_hour_factor(hour):
            if 7 <= hour <= 9 or 17 <= hour <= 19:
                return 0.7
            elif 0 <= hour <= 5:
                return 1.2
            else:
                return 1.0
        
        df_segments['factor_hora'] = df_segments['hora_inicio'].apply(get_hour_factor)
        df_segments['velocidad_esperada'] = (
            df_segments['velocidad_promedio_historica'] * df_segments['factor_hora']
        )
        df_segments['eta_simple'] = (df_segments['distancia_km'] / df_segments['velocidad_esperada']) * 60
        
        self.feature_columns = [
            'distancia_km',
            'bearing',
            'velocidad_origen',
            'velocidad_promedio_historica',
            'velocidad_esperada',
            'hora_inicio',
            'dia_semana',
            'es_fin_semana',
            'factor_hora',
            'eta_simple',
        ]
        
        X = df_segments[self.feature_columns].fillna(0)
        y = df_segments['tiempo_viaje_min']
        
        print(f"✅ Features creados: {X.shape[1]} columnas")
        
        return X, y
    
    def train(self, X, y, test_size=0.2):
        """
        Entrena el modelo
        
        Args:
            X: Features
            y: Target
            test_size: Proporción de datos para test
        
        Returns:
            Métricas de evaluación
        """
        print(f"🤖 Entrenando modelo RandomForestRegressor...")
        
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=test_size, random_state=42
        )
        
        print(f"   • Train: {len(X_train):,} filas")
        print(f"   • Test: {len(X_test):,} filas")
        
        X_train_scaled = self.scaler.fit_transform(X_train)
        self.model.fit(X_train_scaled, y_train)
        
        self.is_trained = True
        print("✅ Modelo entrenado exitosamente")
        
        # Feature importance
        print(f"\n📊 Feature Importance (Top 5):")
        feature_importance = pd.DataFrame({
            'feature': self.feature_columns,
            'importance': self.model.feature_importances_
        }).sort_values('importance', ascending=False)
        
        for idx, row in feature_importance.head(5).iterrows():
            print(f"   • {row['feature']}: {row['importance']:.4f}")
        
        # Evaluar
        metrics = self.evaluate(X_test, y_test)
        
        return metrics
    
    def evaluate(self, X_test, y_test):
        """
        Evalúa el modelo
        
        Args:
            X_test: Features de prueba
            y_test: Target de prueba
        
        Returns:
            Diccionario con métricas
        """
        print("\n📊 Evaluando modelo...")
        
        X_scaled = self.scaler.transform(X_test)
        predictions = self.model.predict(X_scaled)
        
        rmse_val = rmse(y_test, predictions)
        mae_val = mae(y_test, predictions)
        mape = np.mean(np.abs((y_test - predictions) / y_test)) * 100
        r2 = self.model.score(X_scaled, y_test)
        
        print(f"   • RMSE: {rmse_val:.2f} minutos")
        print(f"   • MAE: {mae_val:.2f} minutos")
        print(f"   • MAPE: {mape:.2f}%")
        print(f"   • R² Score: {r2:.4f}")
        
        return {
            'rmse': float(rmse_val),
            'mae': float(mae_val),
            'mape': float(mape),
            'r2_score': float(r2)
        }
    
    def predict(self, features):
        """
        Realiza predicción
        
        Args:
            features: DataFrame o dict con features
        
        Returns:
            Predicción de tiempo en minutos
        """
        if not self.is_trained:
            raise ValueError("Modelo no entrenado. Ejecutar train() primero.")
        
        if isinstance(features, dict):
            features = pd.DataFrame([features])
        
        features_scaled = self.scaler.transform(features[self.feature_columns])
        prediction = self.model.predict(features_scaled)
        
        return prediction[0]
    
    def save(self, path):
        """
        Guarda el modelo entrenado
        
        Args:
            path: Ruta donde guardar el modelo
        """
        if not self.is_trained:
            raise ValueError("Modelo no entrenado. Ejecutar train() primero.")
        
        model_data = {
            'model': self.model,
            'scaler': self.scaler,
            'feature_columns': self.feature_columns,
            'trained_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        joblib.dump(model_data, path)
        print(f"💾 Modelo guardado en: {path}")
    
    @classmethod
    def load(cls, path):
        """
        Carga un modelo entrenado
        
        Args:
            path: Ruta del modelo
        
        Returns:
            Instancia de ETAPredictor con modelo cargado
        """
        model_data = joblib.load(path)
        
        predictor = cls()
        predictor.model = model_data['model']
        predictor.scaler = model_data['scaler']
        predictor.feature_columns = model_data['feature_columns']
        predictor.is_trained = True
        
        print(f"✅ Modelo cargado desde: {path}")
        
        return predictor
