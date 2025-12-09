"""
Modelo de Clasificación de Comportamiento de Empleados
Define la arquitectura para clasificar empleados en categorías de desempeño
"""

import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.preprocessing import StandardScaler
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, confusion_matrix
import joblib
from pathlib import Path
from datetime import datetime

import sys
sys.path.insert(0, str(Path(__file__).parent.parent))

from utils.geo_utils import calculate_distance


class BehaviorClassifier:
    """
    Modelo de clasificación de comportamiento de empleados
    Categorías: eficiente (90-100), normal (60-89), requiere_atencion (0-59)
    """
    
    def __init__(self, n_estimators=100, max_depth=15, random_state=42):
        """
        Inicializa el clasificador de comportamiento
        
        Args:
            n_estimators: Número de árboles en el bosque
            max_depth: Profundidad máxima de los árboles
            random_state: Semilla para reproducibilidad
        """
        self.model = RandomForestClassifier(
            n_estimators=n_estimators,
            max_depth=max_depth,
            min_samples_split=5,
            min_samples_leaf=2,
            class_weight='balanced',
            random_state=random_state,
            n_jobs=-1
        )
        self.scaler = StandardScaler()
        self.feature_columns = None
        self.categories = ['eficiente', 'normal', 'requiere_atencion']
        self.is_trained = False
    
    def create_daily_metrics(self, df_ubicaciones, df_historial=None, df_alertas=None):
        """
        Crea métricas diarias por empleado/dispositivo
        
        Args:
            df_ubicaciones: DataFrame con ubicaciones
            df_historial: DataFrame con historial de zonas (opcional)
            df_alertas: DataFrame con alertas (opcional)
        
        Returns:
            DataFrame con métricas agregadas y categorías
        """
        print("📊 Creando métricas diarias por empleado...")
        
        df_ubicaciones['FechaHora'] = pd.to_datetime(df_ubicaciones['FechaHora'])
        df_ubicaciones['Fecha'] = df_ubicaciones['FechaHora'].dt.date
        
        daily_metrics = []
        
        for (dispositivo_id, fecha), group in df_ubicaciones.groupby(['DispositivoID', 'Fecha']):
            if len(group) < 2:
                continue
            
            # Métricas de velocidad
            velocidades = group['Velocidad'].fillna(0)
            violaciones_velocidad = (velocidades > 90).sum()
            pct_violaciones_velocidad = (violaciones_velocidad / len(velocidades)) * 100
            
            # Métricas de movimiento
            tiempo_detenido = (velocidades < 5).sum()
            tiempo_movimiento = (velocidades >= 5).sum()
            pct_tiempo_movimiento = (tiempo_movimiento / len(velocidades)) * 100
            
            # Distancia total
            distancia_total = 0
            for i in range(1, len(group)):
                prev = group.iloc[i-1]
                curr = group.iloc[i]
                dist = calculate_distance(
                    prev['Latitud'], prev['Longitud'],
                    curr['Latitud'], curr['Longitud']
                )
                distancia_total += dist
            
            # Cambios bruscos
            cambios_velocidad = np.abs(np.diff(velocidades.values))
            cambios_bruscos = (cambios_velocidad > 30).sum()
            
            # Métricas de zonas
            eventos_zona_restringida = 0
            eventos_checkpoint = 0
            
            if df_historial is not None and len(df_historial) > 0:
                df_historial['FechaHoraEvento'] = pd.to_datetime(df_historial['FechaHoraEvento'])
                df_historial['Fecha'] = df_historial['FechaHoraEvento'].dt.date
                
                historial_day = df_historial[
                    (df_historial['DispositivoID'] == dispositivo_id) & 
                    (df_historial['Fecha'] == fecha)
                ]
                
                if len(historial_day) > 0:
                    eventos_zona_restringida = (historial_day['TipoZona'] == 'Zona Restringida').sum()
                    eventos_checkpoint = (historial_day['TipoZona'] == 'Checkpoint').sum()
            
            # Métricas de alertas
            alertas_dia = 0
            alertas_criticas = 0
            
            if df_alertas is not None and len(df_alertas) > 0:
                df_alertas['FechaHora'] = pd.to_datetime(df_alertas['FechaHora'])
                df_alertas['Fecha'] = df_alertas['FechaHora'].dt.date
                
                alertas_day = df_alertas[
                    (df_alertas['DispositivoID'] == dispositivo_id) & 
                    (df_alertas['Fecha'] == fecha)
                ]
                
                if len(alertas_day) > 0:
                    alertas_dia = len(alertas_day)
                    alertas_criticas = (alertas_day['Prioridad'] == 'Crítica').sum()
            
            # Calcular score
            score = self.calculate_score(
                violaciones_velocidad=violaciones_velocidad,
                eventos_zona_restringida=eventos_zona_restringida,
                alertas_criticas=alertas_criticas,
                pct_tiempo_movimiento=pct_tiempo_movimiento,
                cambios_bruscos=cambios_bruscos,
                eventos_checkpoint=eventos_checkpoint
            )
            
            # Clasificar
            categoria = self.score_to_category(score)
            
            metrics = {
                'DispositivoID': dispositivo_id,
                'Fecha': fecha,
                'velocidad_promedio': velocidades.mean(),
                'velocidad_maxima': velocidades.max(),
                'velocidad_std': velocidades.std(),
                'violaciones_velocidad': violaciones_velocidad,
                'pct_violaciones_velocidad': pct_violaciones_velocidad,
                'pct_tiempo_movimiento': pct_tiempo_movimiento,
                'distancia_total_km': distancia_total,
                'cambios_bruscos': cambios_bruscos,
                'eventos_zona_restringida': eventos_zona_restringida,
                'eventos_checkpoint': eventos_checkpoint,
                'alertas_dia': alertas_dia,
                'alertas_criticas': alertas_criticas,
                'puntos_totales': len(velocidades),
                'score': score,
                'categoria': categoria,
            }
            
            daily_metrics.append(metrics)
        
        df_metrics = pd.DataFrame(daily_metrics)
        print(f"✅ Creadas {len(df_metrics):,} observaciones diarias")
        
        print(f"\n📊 Distribución de categorías:")
        cat_counts = df_metrics['categoria'].value_counts()
        for cat, count in cat_counts.items():
            pct = (count / len(df_metrics)) * 100
            print(f"   • {cat}: {count} ({pct:.1f}%)")
        
        return df_metrics
    
    def calculate_score(self, violaciones_velocidad, eventos_zona_restringida, 
                       alertas_criticas, pct_tiempo_movimiento, cambios_bruscos, 
                       eventos_checkpoint):
        """
        Calcula score de comportamiento
        
        Returns:
            Score entre 0 y 100
        """
        score = 100
        
        if violaciones_velocidad > 0:
            score -= 20 * min(violaciones_velocidad / 10, 1)
        
        if eventos_zona_restringida > 0:
            score -= 30 * min(eventos_zona_restringida / 3, 1)
        
        if alertas_criticas > 0:
            score -= 15 * min(alertas_criticas / 2, 1)
        
        if pct_tiempo_movimiento < 30:
            score -= 10
        
        if cambios_bruscos > 5:
            score -= 10
        
        if eventos_checkpoint > 0:
            score += 5 * min(eventos_checkpoint / 2, 1)
        
        return max(0, min(100, score))
    
    def score_to_category(self, score):
        """Convierte score a categoría"""
        if score >= 90:
            return 'eficiente'
        elif score >= 60:
            return 'normal'
        else:
            return 'requiere_atencion'
    
    def create_features(self, df_metrics):
        """
        Prepara features para el clasificador
        
        Args:
            df_metrics: DataFrame con métricas diarias
        
        Returns:
            X (features), y (target)
        """
        print("🔧 Preparando features para clasificación...")
        
        self.feature_columns = [
            'velocidad_promedio',
            'velocidad_maxima',
            'velocidad_std',
            'violaciones_velocidad',
            'pct_violaciones_velocidad',
            'pct_tiempo_movimiento',
            'distancia_total_km',
            'cambios_bruscos',
            'eventos_zona_restringida',
            'eventos_checkpoint',
            'alertas_dia',
            'alertas_criticas',
            'puntos_totales',
            'score',
        ]
        
        X = df_metrics[self.feature_columns].fillna(0)
        y = df_metrics['categoria']
        
        print(f"✅ Features preparados: {X.shape[1]} columnas")
        
        return X, y
    
    def train(self, X, y, test_size=0.2):
        """
        Entrena el modelo
        
        Args:
            X: Features
            y: Target (categorías)
            test_size: Proporción de datos para test
        
        Returns:
            Métricas de evaluación
        """
        print(f"🤖 Entrenando modelo RandomForestClassifier...")
        
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=test_size, random_state=42, stratify=y
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
        
        accuracy = self.model.score(X_scaled, y_test)
        print(f"   • Accuracy: {accuracy:.4f} ({accuracy*100:.2f}%)")
        
        report = classification_report(y_test, predictions, output_dict=True)
        
        return {
            'accuracy': float(accuracy),
            'classification_report': report
        }
    
    def predict(self, features):
        """
        Clasifica comportamiento
        
        Args:
            features: DataFrame o dict con features
        
        Returns:
            Categoría predicha
        """
        if not self.is_trained:
            raise ValueError("Modelo no entrenado. Ejecutar train() primero.")
        
        if isinstance(features, dict):
            features = pd.DataFrame([features])
        
        features_scaled = self.scaler.transform(features[self.feature_columns])
        prediction = self.model.predict(features_scaled)
        
        return prediction[0]
    
    def save(self, path):
        """Guarda el modelo entrenado"""
        if not self.is_trained:
            raise ValueError("Modelo no entrenado. Ejecutar train() primero.")
        
        model_data = {
            'model': self.model,
            'scaler': self.scaler,
            'feature_columns': self.feature_columns,
            'categories': self.categories,
            'trained_date': datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        }
        
        joblib.dump(model_data, path)
        print(f"💾 Modelo guardado en: {path}")
    
    @classmethod
    def load(cls, path):
        """Carga un modelo entrenado"""
        model_data = joblib.load(path)
        
        classifier = cls()
        classifier.model = model_data['model']
        classifier.scaler = model_data['scaler']
        classifier.feature_columns = model_data['feature_columns']
        classifier.categories = model_data['categories']
        classifier.is_trained = True
        
        print(f"✅ Modelo cargado desde: {path}")
        
        return classifier
