"""
Modelo de Detección de Anomalías
Define la arquitectura para detectar comportamiento anómalo en empleados
"""

import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
import joblib
from pathlib import Path
from datetime import datetime

import sys
sys.path.insert(0, str(Path(__file__).parent.parent))

from utils.geo_utils import calculate_distance


class AnomalyDetector:
    """
    Modelo de detección de anomalías usando IsolationForest
    Detecta: exceso de velocidad, paradas prolongadas, comportamiento errático
    """
    
    def __init__(self, contamination=0.15, n_estimators=100, random_state=42):
        """
        Inicializa el detector de anomalías
        
        Args:
            contamination: Proporción esperada de anomalías (0.15 = 15%)
            n_estimators: Número de árboles
            random_state: Semilla para reproducibilidad
        """
        self.model = IsolationForest(
            contamination=contamination,
            random_state=random_state,
            n_estimators=n_estimators,
            max_samples='auto',
            n_jobs=-1
        )
        self.scaler = StandardScaler()
        self.feature_columns = None
        self.contamination = contamination
        self.is_trained = False
    
    def create_features(self, df_ubicaciones):
        """
        Crea features para detección de anomalías
        
        Args:
            df_ubicaciones: DataFrame con ubicaciones
        
        Returns:
            DataFrame con features
        """
        print("🔧 Creando features para detección de anomalías...")
        
        df_ubicaciones['FechaHora'] = pd.to_datetime(df_ubicaciones['FechaHora'])
        df_ubicaciones = df_ubicaciones.sort_values(['DispositivoID', 'FechaHora']).reset_index(drop=True)
        
        features_df = pd.DataFrame()
        features_df['velocidad'] = df_ubicaciones['Velocidad'].fillna(0)
        features_df['hora_dia'] = df_ubicaciones['FechaHora'].dt.hour
        features_df['dia_semana'] = df_ubicaciones['FechaHora'].dt.dayofweek
        features_df['es_fin_semana'] = features_df['dia_semana'].isin([5, 6]).astype(int)
        
        features_df['cambio_velocidad'] = 0.0
        features_df['distancia_metros'] = 0.0
        features_df['tiempo_detenido'] = (features_df['velocidad'] < 5).astype(int)
        
        print("  • Calculando cambios de velocidad y distancias...")
        for dispositivo_id in df_ubicaciones['DispositivoID'].unique():
            mask = df_ubicaciones['DispositivoID'] == dispositivo_id
            idx = df_ubicaciones[mask].index
            
            if len(idx) < 2:
                continue
            
            velocidades = df_ubicaciones.loc[idx, 'Velocidad'].values
            cambios = np.abs(np.diff(velocidades, prepend=velocidades[0]))
            features_df.loc[idx, 'cambio_velocidad'] = cambios
            
            lats = df_ubicaciones.loc[idx, 'Latitud'].values
            lons = df_ubicaciones.loc[idx, 'Longitud'].values
            
            distancias = [0]
            for i in range(1, len(lats)):
                dist = calculate_distance(lats[i-1], lons[i-1], lats[i], lons[i])
                distancias.append(dist * 1000)
            
            features_df.loc[idx, 'distancia_metros'] = distancias
        
        print("  • Calculando estadísticos por ventana móvil...")
        window_size = 5
        
        for dispositivo_id in df_ubicaciones['DispositivoID'].unique():
            mask = df_ubicaciones['DispositivoID'] == dispositivo_id
            idx = df_ubicaciones[mask].index
            
            if len(idx) < window_size:
                continue
            
            velocidad_window = features_df.loc[idx, 'velocidad'].rolling(window_size, min_periods=1).mean()
            features_df.loc[idx, 'velocidad_media_window'] = velocidad_window
            
            velocidad_std = features_df.loc[idx, 'velocidad'].rolling(window_size, min_periods=1).std().fillna(0)
            features_df.loc[idx, 'velocidad_std_window'] = velocidad_std
        
        features_df = features_df.fillna(0)
        
        self.feature_columns = list(features_df.columns)
        
        print(f"✅ Features creados: {features_df.shape[1]} columnas")
        
        return features_df
    
    def train(self, X, test_size=0.2):
        """
        Entrena el modelo
        
        Args:
            X: Features
            test_size: Proporción de datos para test
        
        Returns:
            Métricas de evaluación
        """
        print(f"🤖 Entrenando modelo IsolationForest...")
        print(f"   • Contamination: {self.contamination}")
        
        X_train, X_test = train_test_split(X, test_size=test_size, random_state=42)
        
        print(f"   • Train: {len(X_train):,} filas")
        print(f"   • Test: {len(X_test):,} filas")
        
        X_train_scaled = self.scaler.fit_transform(X_train)
        self.model.fit(X_train_scaled)
        
        self.is_trained = True
        print("✅ Modelo entrenado exitosamente")
        
        metrics = self.evaluate(X_test)
        
        return metrics
    
    def evaluate(self, X_test):
        """
        Evalúa el modelo
        
        Args:
            X_test: Features de prueba
        
        Returns:
            Diccionario con métricas
        """
        print("\n📊 Evaluando modelo...")
        
        X_scaled = self.scaler.transform(X_test)
        predictions = self.model.predict(X_scaled)
        scores = self.model.score_samples(X_scaled)
        
        n_anomalias = np.sum(predictions == -1)
        pct_anomalias = (n_anomalias / len(predictions)) * 100
        
        print(f"   • Anomalías detectadas: {n_anomalias} ({pct_anomalias:.2f}%)")
        print(f"   • Normales: {np.sum(predictions == 1)} ({100-pct_anomalias:.2f}%)")
        print(f"   • Score promedio: {scores.mean():.4f}")
        
        return {
            'test_anomalies': int(n_anomalias),
            'test_anomaly_rate': float(pct_anomalias)
        }
    
    def predict(self, features):
        """
        Detecta anomalías
        
        Args:
            features: DataFrame o dict con features
        
        Returns:
            Tuple (is_anomaly, score)
        """
        if not self.is_trained:
            raise ValueError("Modelo no entrenado. Ejecutar train() primero.")
        
        if isinstance(features, dict):
            features = pd.DataFrame([features])
        
        features_scaled = self.scaler.transform(features[self.feature_columns])
        prediction = self.model.predict(features_scaled)
        score = self.model.score_samples(features_scaled)
        
        is_anomaly = prediction[0] == -1
        
        return is_anomaly, score[0]
    
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
            'contamination': self.contamination,
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
            Instancia de AnomalyDetector con modelo cargado
        """
        model_data = joblib.load(path)
        
        detector = cls(contamination=model_data.get('contamination', 0.15))
        detector.model = model_data['model']
        detector.scaler = model_data['scaler']
        detector.feature_columns = model_data['feature_columns']
        detector.is_trained = True
        
        print(f"✅ Modelo cargado desde: {path}")
        
        return detector
