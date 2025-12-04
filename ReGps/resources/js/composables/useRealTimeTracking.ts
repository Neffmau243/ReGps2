import { ref } from 'vue'

interface Ubicacion {
  UbicacionID: number
  DispositivoID: number
  Latitud: number
  Longitud: number
  FechaHora: string
  dispositivo: {
    DispositivoID: number
    Nombre: string
    Modelo?: string
    IMEI?: string
    empleado: {
      EmpleadoID: number
      Nombre: string
      Apellido: string
    } | null
  }
  empleado?: {
    EmpleadoID: number
    Nombre: string
    Apellido: string
  } | null
}

export function useRealTimeTracking() {
  const ubicaciones = ref<Map<number, Ubicacion>>(new Map())
  const isConnected = ref(false)
  const lastUpdate = ref<Ubicacion | null>(null)

  let channel: any = null

  const conectar = () => {
    try {
      // @ts-ignore - Echo está disponible globalmente desde app.ts
      if (window.Echo) {
        // @ts-ignore
        channel = window.Echo.channel('ubicaciones')
          .listen('UbicacionActualizada', (data: Ubicacion) => {
            // Actualizar o agregar la ubicación del dispositivo
            ubicaciones.value.set(data.DispositivoID, data)
            lastUpdate.value = data
            
            console.log('📍 Nueva ubicación recibida:', data)
          })

        isConnected.value = true
        console.log('✅ Conectado al canal de ubicaciones en tiempo real')
      } else {
        console.warn('⚠️ Echo no está disponible. Asegúrate de que Reverb esté corriendo.')
        isConnected.value = false
      }
    } catch (error) {
      console.error('❌ Error al conectar con WebSocket:', error)
      isConnected.value = false
    }
  }

  const desconectar = () => {
    if (channel) {
      try {
        // @ts-ignore
        window.Echo.leaveChannel('ubicaciones')
        channel = null
        isConnected.value = false
        console.log('🔌 Desconectado del canal de ubicaciones')
      } catch (error) {
        console.error('Error al desconectar:', error)
      }
    }
  }

  const getUbicacion = (dispositivoId: number): Ubicacion | undefined => {
    return ubicaciones.value.get(dispositivoId)
  }

  const getAllUbicaciones = (): Ubicacion[] => {
    return Array.from(ubicaciones.value.values())
  }

  return {
    ubicaciones,
    isConnected,
    lastUpdate,
    conectar,
    desconectar,
    getUbicacion,
    getAllUbicaciones
  }
}
