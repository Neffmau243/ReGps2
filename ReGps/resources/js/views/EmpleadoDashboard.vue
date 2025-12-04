<template>
  <div class="empleado-dashboard">
    <div class="container py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">Mi Panel de Control</h1>
        <p class="text-gray-400">Gestiona tu rastreo GPS y dispositivos</p>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- GPS Control Panel -->
        <div class="lg:col-span-2">
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">
                Control de Rastreo GPS
              </h2>
            </div>
            <div class="card-body">
              <!-- GPS Status -->
              <div class="mb-6 p-4 rounded-lg" :class="trackingActive ? 'bg-success/10 border border-success/30' : 'bg-dark-200 border border-dark-300'">
                <div class="flex items-center justify-between mb-4">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center" :class="trackingActive ? 'bg-success' : 'bg-dark-300'">
                    </div>
                    <div>
                      <h3 class="text-lg font-bold text-white">
                        {{ trackingActive ? 'Rastreo Activo' : 'Rastreo Inactivo' }}
                      </h3>
                      <p class="text-sm text-gray-400">
                        {{ trackingActive ? 'Enviando ubicación cada 30 segundos' : 'Presiona el botón para iniciar' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Control Button -->
                <button 
                  @click="toggleTracking"
                  class="btn w-full"
                  :class="trackingActive ? 'btn-danger' : 'btn-primary'"
                  :disabled="!selectedDevice"
                >
                  <i :class="trackingActive ? 'bi bi-stop-circle-fill' : 'bi bi-play-circle-fill'"></i>
                  {{ trackingActive ? 'Detener Rastreo' : 'Iniciar Rastreo' }}
                </button>

                <p v-if="!selectedDevice" class="text-warning text-sm mt-2 text-center">
                  Selecciona un dispositivo para comenzar
                </p>
              </div>

              <!-- Current Location -->
              <div v-if="currentLocation" class="bg-dark-200 rounded-lg p-4 border border-dark-300">
                <h3 class="text-sm font-bold text-white mb-3 flex items-center gap-2">
                  <i class="bi bi-geo-alt-fill text-primary"></i>
                  Última Ubicación
                </h3>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <p class="text-xs text-gray-400 mb-1">Latitud</p>
                    <p class="text-white font-mono">{{ currentLocation.lat.toFixed(6) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-400 mb-1">Longitud</p>
                    <p class="text-white font-mono">{{ currentLocation.lng.toFixed(6) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-400 mb-1">Velocidad</p>
                    <p class="text-white">{{ currentLocation.speed || 0 }} km/h</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-400 mb-1">Hora</p>
                    <p class="text-white">{{ formatTime(currentLocation.timestamp) }}</p>
                  </div>
                </div>
              </div>

              <!-- GPS Error -->
              <div v-if="gpsError" class="alert alert-danger mt-4">
                {{ gpsError }}
              </div>
            </div>
          </div>

          <!-- Map -->
          <div class="card mt-6">
            <div class="card-header">
              <h2 class="card-title">
                Mi Ubicación Actual
              </h2>
            </div>
            <div class="card-body" style="padding: 0;">
              <div id="employeeMap" style="height: 400px; border-radius: 0 0 0.75rem 0.75rem;"></div>
            </div>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Devices -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">
                Mis Dispositivos
              </h2>
            </div>
            <div class="card-body">
              <div v-if="loading" class="text-center py-4">
                <div class="spinner mx-auto"></div>
                <p class="text-primary/80 text-sm mt-2 font-medium">Cargando dispositivos...</p>
              </div>

              <div v-else-if="dispositivos.length === 0" class="text-center py-8">
                <p class="text-gray-400 mt-2">No tienes dispositivos asignados</p>
              </div>

              <div v-else class="space-y-3">
                <div 
                  v-for="device in dispositivos" 
                  :key="device.DispositivoID"
                  @click="selectDevice(device.DispositivoID)"
                  class="device-card"
                  :class="{ 'device-card-active': selectedDevice === device.DispositivoID }"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="device.Estado === 'Activo' ? 'bg-success/20' : 'bg-dark-300'">
                      </div>
                      <div>
                        <p class="text-white font-medium">{{ device.Modelo }}</p>
                        <p class="text-xs text-gray-400">{{ device.IMEI }}</p>
                      </div>
                    </div>
                    <div>
                      <span class="badge" :class="device.Estado === 'Activo' ? 'badge-success' : 'badge-warning'">
                        {{ device.Estado }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="card">
            <div class="card-header">
              <h2 class="card-title">
                Estadísticas de Hoy
              </h2>
            </div>
            <div class="card-body">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-gray-400">Tiempo Activo</span>
                  </div>
                  <span class="text-white font-bold">{{ stats.activeTime }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-gray-400">Ubicaciones</span>
                  </div>
                  <span class="text-white font-bold">{{ stats.locations }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-gray-400">Vel. Promedio</span>
                  </div>
                  <span class="text-white font-bold">{{ stats.avgSpeed }} km/h</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

interface Dispositivo {
  DispositivoID: number
  IMEI: string
  Modelo: string
  Marca: string
  Estado: string
}

const dispositivos = ref<Dispositivo[]>([])
const selectedDevice = ref<number | null>(null)
const trackingActive = ref(false)
const currentLocation = ref<any>(null)
const gpsError = ref('')
const loading = ref(false)
const stats = ref({
  activeTime: '0h 0m',
  locations: 0,
  avgSpeed: 0
})

let map: L.Map | null = null
let marker: L.Marker | null = null
let trackingInterval: number | null = null

const loadDispositivos = async () => {
  loading.value = true
  try {
    const response = await api.get('/dispositivos/mis-dispositivos')
    console.log('📱 DISPOSITIVOS CARGADOS:', response.data)
    dispositivos.value = response.data
    
    // Auto-seleccionar el primer dispositivo activo
    const activeDevice = dispositivos.value.find(d => d.Estado === 'Activo')
    if (activeDevice) {
      selectedDevice.value = activeDevice.DispositivoID
      console.log('✅ DISPOSITIVO AUTO-SELECCIONADO:', activeDevice)
    } else {
      console.log('⚠️ NO HAY DISPOSITIVOS ACTIVOS')
    }
  } catch (error: any) {
    console.error('❌ Error al cargar dispositivos:', error)
    gpsError.value = 'Error al cargar tus dispositivos'
  } finally {
    loading.value = false
  }
}

const selectDevice = (deviceId: number) => {
  if (trackingActive.value) {
    alert('Detén el rastreo antes de cambiar de dispositivo')
    return
  }
  console.log('🔄 DISPOSITIVO SELECCIONADO MANUALMENTE:', deviceId)
  selectedDevice.value = deviceId
}

const initMap = () => {
  if (map) return
  
  map = L.map('employeeMap').setView([-12.0464, -77.0428], 13)
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map)
}

const updateMap = (lat: number, lng: number) => {
  if (!map) return
  
  map.setView([lat, lng], 15)
  
  if (marker) {
    marker.setLatLng([lat, lng])
  } else {
    const customIcon = L.divIcon({
      className: 'custom-marker',
      html: '<div class="custom-marker bg-primary"></div>',
      iconSize: [30, 30]
    })
    
    marker = L.marker([lat, lng], { icon: customIcon }).addTo(map)
  }
}

const sendLocation = async (position: GeolocationPosition) => {
  if (!selectedDevice.value) return
  
  console.log('📍 POSICIÓN GPS RECIBIDA del navegador:', {
    latitud: position.coords.latitude,
    longitud: position.coords.longitude,
    velocidad_ms: position.coords.speed,
    precisión: position.coords.accuracy,
    timestamp: new Date(position.timestamp).toISOString()
  })
  
  try {
    const locationData = {
      DispositivoID: selectedDevice.value,
      Latitud: position.coords.latitude,
      Longitud: position.coords.longitude,
      Velocidad: position.coords.speed ? (position.coords.speed * 3.6) : 0, // m/s a km/h
      Direccion: 'Ubicación en tiempo real',
      FechaHora: new Date().toISOString()
    }
    
    console.log('🚀 ENVIANDO A LA BASE DE DATOS:', locationData)
    
    const response = await api.post('/ubicaciones', locationData)
    console.log('✅ RESPUESTA DEL SERVIDOR:', response.data)
    
    currentLocation.value = {
      lat: position.coords.latitude,
      lng: position.coords.longitude,
      speed: locationData.Velocidad.toFixed(1),
      timestamp: new Date()
    }
    
    updateMap(position.coords.latitude, position.coords.longitude)
    stats.value.locations++
    
    gpsError.value = ''
  } catch (error: any) {
    console.error('❌ ERROR AL ENVIAR UBICACIÓN:', error)
    console.error('❌ Detalles del error:', error.response?.data)
    gpsError.value = 'Error al enviar ubicación al servidor'
  }
}

const toggleTracking = () => {
  if (trackingActive.value) {
    stopTracking()
  } else {
    startTracking()
  }
}

const startTracking = () => {
  if (!selectedDevice.value) {
    alert('Selecciona un dispositivo primero')
    return
  }
  
  if (!navigator.geolocation) {
    gpsError.value = 'Tu navegador no soporta geolocalización'
    return
  }
  
  gpsError.value = ''
  trackingActive.value = true
  
  // Función para obtener y enviar ubicación
  const getAndSendLocation = () => {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        sendLocation(position)
      },
      (error) => {
        console.error('❌ Error GPS:', error)
        gpsError.value = `Error GPS: ${error.message}`
      },
      { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    )
  }
  
  // Enviar ubicación inicial inmediatamente
  getAndSendLocation()
  
  // Configurar intervalo para enviar cada 30 segundos
  trackingInterval = window.setInterval(() => {
    if (trackingActive.value) {
      console.log('⏱️ Intervalo de 30s - Obteniendo nueva ubicación...')
      getAndSendLocation()
    }
  }, 30000) // 30 segundos
  
  console.log('✅ Rastreo iniciado - Enviando ubicación cada 30 segundos')
}

const stopTracking = () => {
  trackingActive.value = false
  
  if (trackingInterval) {
    clearInterval(trackingInterval)
    trackingInterval = null
    console.log('🛑 Rastreo detenido - Intervalo limpiado')
  }
}

const formatTime = (date: Date) => {
  return date.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
}

onMounted(() => {
  loadDispositivos()
  setTimeout(() => initMap(), 100)
})

onUnmounted(() => {
  stopTracking()
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<style scoped>
.device-card {
  padding: 1rem;
  background-color: var(--color-dark-200);
  border: 1px solid var(--color-dark-300);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.device-card:hover {
  border-color: var(--color-primary);
  background-color: var(--color-dark-100);
}

.device-card-active {
  border-color: var(--color-primary);
  background-color: rgba(255, 107, 53, 0.1);
}

#employeeMap {
  border-radius: 0 0 0.75rem 0.75rem;
}
</style>
