"""
Métricas personalizadas para evaluación de modelos ML

Incluye métricas para:
- Regresión (ETA prediction)
- Clasificación (Driver behavior)
- Anomaly detection
"""

import numpy as np
from sklearn.metrics import (
    mean_squared_error, mean_absolute_error, r2_score,
    accuracy_score, precision_score, recall_score, f1_score,
    confusion_matrix, classification_report,
    roc_auc_score, roc_curve
)


# ============================================================================
# MÉTRICAS DE REGRESIÓN (para predicción de ETA, distancias, etc.)
# ============================================================================

def rmse(y_true, y_pred):
    """
    Root Mean Squared Error (RMSE)
    Penaliza más los errores grandes
    
    Args:
        y_true: Valores reales
        y_pred: Valores predichos
    
    Returns:
        RMSE score
    """
    return np.sqrt(mean_squared_error(y_true, y_pred))


def mae(y_true, y_pred):
    """
    Mean Absolute Error (MAE)
    Error absoluto promedio
    
    Args:
        y_true: Valores reales
        y_pred: Valores predichos
    
    Returns:
        MAE score
    """
    return mean_absolute_error(y_true, y_pred)


def mape(y_true, y_pred):
    """
    Mean Absolute Percentage Error (MAPE)
    Error porcentual promedio
    
    Args:
        y_true: Valores reales
        y_pred: Valores predichos
    
    Returns:
        MAPE score (en porcentaje)
    """
    y_true, y_pred = np.array(y_true), np.array(y_pred)
    # Evitar división por cero
    mask = y_true != 0
    return np.mean(np.abs((y_true[mask] - y_pred[mask]) / y_true[mask])) * 100


def evaluate_regression_model(y_true, y_pred, model_name='Model'):
    """
    Evalúa un modelo de regresión con múltiples métricas
    
    Args:
        y_true: Valores reales
        y_pred: Valores predichos
        model_name: Nombre del modelo
    
    Returns:
        Dict con todas las métricas
    """
    metrics = {
        'RMSE': rmse(y_true, y_pred),
        'MAE': mae(y_true, y_pred),
        'MAPE': mape(y_true, y_pred),
        'R2': r2_score(y_true, y_pred)
    }
    
    print(f"\n{'='*50}")
    print(f"📊 Métricas de Regresión: {model_name}")
    print(f"{'='*50}")
    print(f"RMSE:  {metrics['RMSE']:.4f}")
    print(f"MAE:   {metrics['MAE']:.4f}")
    print(f"MAPE:  {metrics['MAPE']:.2f}%")
    print(f"R²:    {metrics['R2']:.4f}")
    print(f"{'='*50}\n")
    
    return metrics


# ============================================================================
# MÉTRICAS DE CLASIFICACIÓN (para clasificación de conductores)
# ============================================================================

def evaluate_classification_model(y_true, y_pred, y_pred_proba=None, 
                                  class_names=None, model_name='Model'):
    """
    Evalúa un modelo de clasificación con múltiples métricas
    
    Args:
        y_true: Etiquetas reales
        y_pred: Etiquetas predichas
        y_pred_proba: Probabilidades predichas (opcional, para ROC-AUC)
        class_names: Nombres de las clases (opcional)
        model_name: Nombre del modelo
    
    Returns:
        Dict con todas las métricas
    """
    # Determinar si es binario o multiclase
    n_classes = len(np.unique(y_true))
    is_binary = n_classes == 2
    
    # Calcular métricas básicas
    accuracy = accuracy_score(y_true, y_pred)
    
    if is_binary:
        precision = precision_score(y_true, y_pred)
        recall = recall_score(y_true, y_pred)
        f1 = f1_score(y_true, y_pred)
    else:
        # Para multiclase, usar promedio weighted
        precision = precision_score(y_true, y_pred, average='weighted')
        recall = recall_score(y_true, y_pred, average='weighted')
        f1 = f1_score(y_true, y_pred, average='weighted')
    
    metrics = {
        'Accuracy': accuracy,
        'Precision': precision,
        'Recall': recall,
        'F1-Score': f1
    }
    
    # Calcular ROC-AUC si se proporcionaron probabilidades
    if y_pred_proba is not None:
        if is_binary:
            # Para binario, usar columna de clase positiva
            if y_pred_proba.ndim > 1:
                y_pred_proba = y_pred_proba[:, 1]
            metrics['ROC-AUC'] = roc_auc_score(y_true, y_pred_proba)
        else:
            # Para multiclase, usar one-vs-rest
            try:
                metrics['ROC-AUC'] = roc_auc_score(y_true, y_pred_proba, 
                                                   multi_class='ovr', average='weighted')
            except:
                pass
    
    # Imprimir resultados
    print(f"\n{'='*50}")
    print(f"📊 Métricas de Clasificación: {model_name}")
    print(f"{'='*50}")
    print(f"Accuracy:  {metrics['Accuracy']:.4f}")
    print(f"Precision: {metrics['Precision']:.4f}")
    print(f"Recall:    {metrics['Recall']:.4f}")
    print(f"F1-Score:  {metrics['F1-Score']:.4f}")
    if 'ROC-AUC' in metrics:
        print(f"ROC-AUC:   {metrics['ROC-AUC']:.4f}")
    
    # Matriz de confusión
    cm = confusion_matrix(y_true, y_pred)
    print(f"\n📊 Matriz de Confusión:")
    print(cm)
    
    # Reporte detallado
    print(f"\n📋 Reporte de Clasificación:")
    print(classification_report(y_true, y_pred, target_names=class_names))
    
    print(f"{'='*50}\n")
    
    return metrics


# ============================================================================
# MÉTRICAS DE DETECCIÓN DE ANOMALÍAS
# ============================================================================

def evaluate_anomaly_detection(y_true, y_pred, y_scores=None, model_name='Model'):
    """
    Evalúa un modelo de detección de anomalías
    
    Args:
        y_true: Etiquetas reales (1=anomalía, 0=normal)
        y_pred: Etiquetas predichas (1=anomalía, 0=normal)
        y_scores: Scores de anomalía (opcional)
        model_name: Nombre del modelo
    
    Returns:
        Dict con todas las métricas
    """
    # Convertir -1 a 1 si es necesario (algunos modelos usan -1 para anomalía)
    y_pred_binary = np.where(y_pred == -1, 1, y_pred)
    
    # Calcular métricas
    cm = confusion_matrix(y_true, y_pred_binary)
    
    # True Negatives, False Positives, False Negatives, True Positives
    tn, fp, fn, tp = cm.ravel()
    
    accuracy = accuracy_score(y_true, y_pred_binary)
    precision = precision_score(y_true, y_pred_binary, zero_division=0)
    recall = recall_score(y_true, y_pred_binary, zero_division=0)
    f1 = f1_score(y_true, y_pred_binary, zero_division=0)
    
    # Tasa de falsos positivos
    false_positive_rate = fp / (fp + tn) if (fp + tn) > 0 else 0
    
    metrics = {
        'Accuracy': accuracy,
        'Precision': precision,
        'Recall': recall,
        'F1-Score': f1,
        'False Positive Rate': false_positive_rate,
        'True Positives': tp,
        'False Positives': fp,
        'True Negatives': tn,
        'False Negatives': fn
    }
    
    # Calcular ROC-AUC si se proporcionaron scores
    if y_scores is not None:
        try:
            metrics['ROC-AUC'] = roc_auc_score(y_true, y_scores)
        except:
            pass
    
    # Imprimir resultados
    print(f"\n{'='*50}")
    print(f"🔍 Métricas de Detección de Anomalías: {model_name}")
    print(f"{'='*50}")
    print(f"Accuracy:  {metrics['Accuracy']:.4f}")
    print(f"Precision: {metrics['Precision']:.4f}")
    print(f"Recall:    {metrics['Recall']:.4f}")
    print(f"F1-Score:  {metrics['F1-Score']:.4f}")
    print(f"FPR:       {metrics['False Positive Rate']:.4f}")
    if 'ROC-AUC' in metrics:
        print(f"ROC-AUC:   {metrics['ROC-AUC']:.4f}")
    
    print(f"\n📊 Matriz de Confusión:")
    print(f"                 Predicho")
    print(f"              Normal  Anomalía")
    print(f"Real Normal    {tn:5d}    {fp:5d}")
    print(f"     Anomalía  {fn:5d}    {tp:5d}")
    
    print(f"{'='*50}\n")
    
    return metrics


# ============================================================================
# MÉTRICAS PERSONALIZADAS PARA GPS
# ============================================================================

def calculate_eta_accuracy(y_true_eta, y_pred_eta, tolerance_minutes=5):
    """
    Calcula qué porcentaje de predicciones de ETA están dentro de un margen de error
    
    Args:
        y_true_eta: Tiempos reales (en minutos)
        y_pred_eta: Tiempos predichos (en minutos)
        tolerance_minutes: Margen de error aceptable (default 5 min)
    
    Returns:
        Porcentaje de predicciones dentro del margen
    """
    errors = np.abs(y_true_eta - y_pred_eta)
    within_tolerance = (errors <= tolerance_minutes).sum()
    accuracy = (within_tolerance / len(y_true_eta)) * 100
    
    return accuracy


def calculate_route_efficiency_score(predicted_distance, actual_distance):
    """
    Calcula qué tan eficiente fue la ruta predicha vs la real
    
    Args:
        predicted_distance: Distancia predicha (km)
        actual_distance: Distancia real recorrida (km)
    
    Returns:
        Score de eficiencia (100 = perfecto, <100 = subestimó, >100 = sobreestimó)
    """
    if actual_distance == 0:
        return 0
    
    efficiency_score = (predicted_distance / actual_distance) * 100
    
    return efficiency_score
