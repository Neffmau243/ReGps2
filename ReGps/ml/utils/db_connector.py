"""
Utilidad para conectar a la base de datos de Laravel
"""

import pymysql
from pymysql.cursors import DictCursor
import pandas as pd
import logging
import os
import sys
from pathlib import Path

# Agregar el directorio raíz al path
sys.path.insert(0, str(Path(__file__).parent.parent))

from config import DB_CONFIG

logger = logging.getLogger(__name__)


class DatabaseConnector:
    """Conexión a la base de datos de Laravel"""
    
    def __init__(self):
        self.config = DB_CONFIG
        self.connection = None
    
    def connect(self):
        """Establecer conexión a la base de datos"""
        try:
            self.connection = pymysql.connect(
                host=self.config['host'],
                port=self.config['port'],
                user=self.config['user'],
                password=self.config['password'],
                database=self.config['database'],
                charset=self.config['charset'],
                cursorclass=DictCursor
            )
            logger.info("✅ Conexión a base de datos establecida")
            return self.connection
        except Exception as e:
            logger.error(f"❌ Error al conectar a la base de datos: {e}")
            raise
    
    def execute_query(self, query, params=None):
        """Ejecutar una consulta SQL y devolver resultados como DataFrame"""
        if not self.connection:
            self.connect()
        
        try:
            df = pd.read_sql(query, self.connection, params=params)
            logger.info(f"✅ Query ejecutada: {len(df)} filas obtenidas")
            return df
        except Exception as e:
            logger.error(f"❌ Error al ejecutar query: {e}")
            raise
    
    def close(self):
        """Cerrar conexión"""
        if self.connection:
            self.connection.close()
            logger.info("🔒 Conexión cerrada")
    
    def __enter__(self):
        """Context manager: entrada"""
        self.connect()
        return self
    
    def __exit__(self, exc_type, exc_val, exc_tb):
        """Context manager: salida"""
        self.close()


# Función helper para uso rápido
def get_ubicaciones(limit=None):
    """Obtener ubicaciones de la base de datos"""
    query = """
    SELECT 
        u.UbicacionID,
        u.DispositivoID,
        u.Latitud,
        u.Longitud,
        u.Velocidad,
        u.Direccion,
        u.FechaHora,
        d.IMEI,
        d.Modelo,
        e.Nombre as EmpleadoNombre
    FROM ubicaciones u
    LEFT JOIN dispositivos d ON u.DispositivoID = d.DispositivoID
    LEFT JOIN empleados e ON d.EmpleadoID = e.EmpleadoID
    ORDER BY u.FechaHora DESC
    """
    
    if limit:
        query += f" LIMIT {limit}"
    
    with DatabaseConnector() as db:
        return db.execute_query(query)


if __name__ == "__main__":
    # Test de conexión
    print("🧪 Probando conexión a base de datos...")
    
    try:
        with DatabaseConnector() as db:
            # Obtener conteo de ubicaciones
            df = db.execute_query("SELECT COUNT(*) as total FROM ubicaciones")
            print(f"\n📊 Total de ubicaciones en la BD: {df['total'].iloc[0]}")
            
            # Obtener últimas 5 ubicaciones
            df_ubicaciones = get_ubicaciones(limit=5)
            print(f"\n📍 Últimas 5 ubicaciones:")
            print(df_ubicaciones[['DispositivoID', 'Latitud', 'Longitud', 'FechaHora']])
            
        print("\n✅ Conexión exitosa!")
        
    except Exception as e:
        print(f"\n❌ Error: {e}")
