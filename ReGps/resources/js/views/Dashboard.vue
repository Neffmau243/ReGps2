<template>
  <div class="dashboard-view">
    <div class="container py-8">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-white mb-2">Dashboard</h1>
          <p class="text-gray-400">Monitoreo en tiempo real de dispositivos GPS</p>
        </div>
        <!-- WebSocket Status Indicator (Mejorado) -->
        <div class="flex items-center gap-2 px-4 py-2 rounded-lg" :class="connectionClass">
          <div class="w-2 h-2 rounded-full" :class="dotClass"></div>
          <span class="text-sm font-medium" :class="textClass">
            {{ connectionText }}
          </span>
        </div>
      </div>
      
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm mb-1 flex items-center gap-2">
                <i class="bi bi-phone-fill text-success"></i>
                Dispositivos Activos
              </p>
              <p class="text-3xl font-bold text-white">{{ stats.activeDevices }}</p>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm mb-1 flex items-center gap-2">
                <i class="bi bi-people-fill text-primary"></i>
                Total Empleados
              </p>
              <p class="text-3xl font-bold text-white">{{ stats.moving }}</p>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm mb-1 flex items-center gap-2">
                <i class="bi bi-bell-fill text-warning"></i>
                Alertas Hoy
              </p>
              <p class="text-3xl font-bold text-white">{{ stats.alerts }}</p>
            </div>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-400 text-sm mb-1 flex items-center gap-2">
                <i class="bi bi-geo-alt-fill text-info"></i>
                Zonas Activas
              </p>
              <p class="text-3xl font-bold text-white">{{ stats.zones }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Map and Device List -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Map -->
        <div class="lg:col-span-2">
          <div class="bg-dark-100 rounded-xl border border-primary/20 overflow-hidden">
            <div class="p-4 border-b border-primary/20 flex items-center justify-between">
              <h2 class="text-xl font-bold text-white flex items-center">
                Mapa en Tiempo Real
              </h2>
              <button 
                @click="refreshLocations"
                class="px-3 py-1.5 bg-primary/10 hover:bg-primary/20 text-primary rounded-lg transition-colors text-sm"
              >
                Actualizar
              </button>
            </div>
            <div id="map" class="h-[500px] bg-dark"></div>
          </div>
        </div>
        
        <!-- Device List -->
        <div class="lg:col-span-1">
          <div class="bg-dark-100 rounded-xl border border-primary/20">
            <div class="p-6 border-b border-primary/20 bg-linear-to-r from-dark-100 to-transparent">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                  <div class="w-11 h-11 rounded-lg bg-primary/10 flex items-center justify-center">
                    <i class="bi bi-phone-vibrate-fill text-primary text-xl"></i>
                  </div>
                  <div>
                    <h2 class="text-xl font-bold text-white mb-1">Dispositivos</h2>
                    <p class="text-gray-500 text-xs mt-1">{{ devices.length }} en tiempo real</p>
                  </div>
                </div>
                <button 
                  @click="refreshLocations"
                  class="w-10 h-10 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary flex items-center justify-center transition-all hover:scale-110"
                  title="Actualizar"
                >
                  <i class="bi bi-arrow-clockwise text-base"></i>
                </button>
              </div>
            </div>
            <div class="p-6 space-y-5 max-h-[500px] overflow-y-auto scrollbar-thin">
              <div 
                v-for="device in sortedDevices" 
                :key="device.device_id"
                class="device-card"
                @click="centerMap(device)"
              >
                <div class="flex items-center gap-4">
                  <!-- Info con mejor tipografía -->
                  <div class="flex-1 min-w-0">
                    <h4 class="text-white font-bold text-base mb-1 truncate">{{ device.device_name }}</h4>
                    <p class="text-gray-400 text-sm mb-1">
                      {{ device.user_name }}
                    </p>
                    <p class="text-gray-500 text-xs">
                      {{ formatTimeAgo(device.minutes_ago) }}
                    </p>
                  </div>
                  
                  <!-- Botón de acción mejorado -->
                  <button 
                    @click.stop="centerMap(device)"
                    class="w-10 h-10 rounded-lg bg-primary/10 hover:bg-primary/20 text-primary flex items-center justify-center transition-all hover:scale-110 shrink-0"
                    title="Ver en mapa"
                  >
                  </button>
                </div>
              </div>
              
              <div v-if="devices.length === 0" class="text-center py-8">
                <p class="text-gray-400">No hay dispositivos activos</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import { useRealTimeTracking } from '@/composables/useRealTimeTracking'

// TypeScript declaration for window.Echo
declare global {
  interface Window {
    Echo: any;
  }
}

interface Device {
  device_id: number
  device_name: string
  user_name: string
  latitude: number
  longitude: number
  minutes_ago: number
}

interface Zona {
  ZonaID: number
  Nombre: string
  TipoZona: 'Checkpoint' | 'Zona Permitida' | 'Zona Restringida'
  TipoGeometria: 'Circulo' | 'Poligono'
  Latitud: number
  Longitud: number
  Radio: number
  Coordenadas: Array<{ lat: number; lng: number }>
  Estado: 'Activo' | 'Inactivo'
  Descripcion?: string
}

const stats = ref({
  activeDevices: 0,
  moving: 0,
  alerts: 0,
  zones: 0
})

const devices = ref<Device[]>([])
const zonas = ref<Zona[]>([])
let map: L.Map | null = null
let markers: L.Marker[] = []
let zoneShapes: (L.Circle | L.Polygon)[] = []
let refreshInterval: number | null = null
let pollingInterval: number | null = null

// Connection state management
const isReconnecting = ref(false)

// Computed: Ordenar dispositivos por actividad (más recientes primero)
const sortedDevices = computed(() => {
  return [...devices.value].sort((a, b) => a.minutes_ago - b.minutes_ago)
})

// Computed: Connection status styling
const connectionClass = computed(() => {
  if (isConnected.value) return 'bg-green-900/30 border border-green-500/30'
  if (isReconnecting.value) return 'bg-yellow-900/30 border border-yellow-500/30'
  return 'bg-red-900/30 border border-red-500/30'
})

const dotClass = computed(() => {
  if (isConnected.value) return 'bg-green-500 animate-pulse'
  if (isReconnecting.value) return 'bg-yellow-500 animate-spin'
  return 'bg-red-500'
})

const textClass = computed(() => {
  if (isConnected.value) return 'text-green-400'
  if (isReconnecting.value) return 'text-yellow-400'
  return 'text-red-400'
})

const connectionText = computed(() => {
  if (isConnected.value) return '🟢 Conectado en vivo'
  if (isReconnecting.value) return '🔄 Reconectando...'
  return '🔴 Sin conexión en tiempo real'
})

// Real-time tracking
const { 
  conectar, 
  desconectar, 
  lastUpdate, 
  getAllUbicaciones,
  isConnected 
} = useRealTimeTracking()

onMounted(async () => {
  initMap()
  await loadData(true) // Ajustar vista SOLO en carga inicial
  
  // Conectar WebSocket para actualizaciones en tiempo real
  conectar()
  
  // Escuchar eventos de conexión de Pusher
  setupConnectionListeners()
  
  // Auto-refresh every 30 seconds (fallback)
  refreshInterval = window.setInterval(() => {
    refreshLocations()
  }, 30000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
  if (pollingInterval) {
    clearInterval(pollingInterval)
  }
  // Desconectar WebSocket
  desconectar()
})

// Watch for real-time updates
watch(lastUpdate, (newUpdate) => {
  if (newUpdate) {
    updateDeviceFromWebSocket(newUpdate)
  }
})

// Watch connection status for fallback polling
watch(isConnected, (connected) => {
  if (!connected && !pollingInterval) {
    // WebSocket desconectado, activar polling como respaldo
    console.warn('⚠️ WebSocket desconectado, activando polling cada 15 segundos')
    
    pollingInterval = window.setInterval(async () => {
      try {
        await refreshLocations()
      } catch (error) {
        console.error('Error en polling fallback:', error)
      }
    }, 15000)
    
  } else if (connected && pollingInterval) {
    // WebSocket restaurado, desactivar polling
    console.log('✅ WebSocket restaurado, desactivando polling fallback')
    clearInterval(pollingInterval)
    pollingInterval = null
  }
})

// Setup connection event listeners
const setupConnectionListeners = () => {
  if (window.Echo?.connector?.pusher) {
    const connection = window.Echo.connector.pusher.connection
    
    connection.bind('connected', () => {
      console.log('✅ WebSocket conectado')
      isReconnecting.value = false
    })
    
    connection.bind('disconnected', () => {
      console.log('🔴 WebSocket desconectado')
      isReconnecting.value = false
    })
    
    connection.bind('connecting', () => {
      console.log('🔄 Intentando reconectar...')
      isReconnecting.value = true
    })
    
    connection.bind('unavailable', () => {
      console.error('❌ WebSocket no disponible')
      isReconnecting.value = false
    })
  }
}

const initMap = () => {
  map = L.map('map').setView([-12.0464, -77.0428], 13)
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map)
}

const loadData = async (initialLoad = false) => {
  try {
    // Load current locations
    const locationsRes = await api.get('/locations/current')
    devices.value = locationsRes.data
    
    // Update map markers (solo ajustar vista en carga inicial)
    updateMarkers(initialLoad)
    
    // Load stats
    stats.value.activeDevices = devices.value.length
    stats.value.moving = devices.value.filter(d => d.minutes_ago < 5).length
    
    // Load alerts count
    const alertsRes = await api.get('/alertas')
    stats.value.alerts = alertsRes.data.filter((a: any) => a.Estado === 'Pendiente').length
    
    // Load zones
    const zonasRes = await api.get('/zonas')
    zonas.value = zonasRes.data
    stats.value.zones = zonasRes.data.filter((z: any) => z.Estado === 'Activo').length
    
    // Update zones on map
    updateZones()
    
  } catch (error) {
    console.error('Error loading data:', error)
  }
}

const updateMarkers = (fitToView = false) => {
  // Clear existing markers
  markers.forEach(marker => marker.remove())
  markers = []
  
  if (!map) return
  
  // Add new markers
  devices.value.forEach(device => {
    const icon = L.divIcon({
      html: `<div class="custom-marker ${getMarkerColor(device.minutes_ago)}">
      </div>`,
      className: '',
      iconSize: [30, 30]
    })
    
    const marker = L.marker([device.latitude, device.longitude], { icon })
      .bindPopup(`
        <div class="text-dark">
          <strong>${device.device_name}</strong><br>
          ${device.user_name}<br>
          <small>${formatTimeAgo(device.minutes_ago)}</small>
        </div>
      `)
      .addTo(map!)
    
    markers.push(marker)
  })
  
  // Solo ajustar vista si se solicita explícitamente (carga inicial)
  if (fitToView && devices.value.length > 0) {
    const bounds = L.latLngBounds(devices.value.map(d => [d.latitude, d.longitude]))
    map.fitBounds(bounds, { padding: [50, 50] })
  }
}

const updateZones = () => {
  // Clear existing zone shapes
  zoneShapes.forEach(shape => shape.remove())
  zoneShapes = []
  
  if (!map) return
  
  // Add zones to map
  zonas.value.forEach(zona => {
    if (zona.Estado !== 'Activo') return
    
    let shape: L.Circle | L.Polygon
    const color = getZoneColor(zona.TipoZona)
    const fillOpacity = 0.2
    const weight = 2
    
    if (zona.TipoGeometria === 'Circulo' && zona.Latitud && zona.Longitud && zona.Radio) {
      // Create circle
      shape = L.circle([zona.Latitud, zona.Longitud], {
        radius: zona.Radio,
        color: color,
        fillColor: color,
        fillOpacity: fillOpacity,
        weight: weight
      })
    } else if (zona.TipoGeometria === 'Poligono' && zona.Coordenadas && zona.Coordenadas.length >= 3) {
      // Create polygon
      const latLngs: [number, number][] = zona.Coordenadas.map(coord => [coord.lat, coord.lng])
      shape = L.polygon(latLngs, {
        color: color,
        fillColor: color,
        fillOpacity: fillOpacity,
        weight: weight
      })
    } else {
      return // Skip invalid zones
    }
    
    // Add popup with zone info
    const icon = getZoneIcon(zona.TipoZona)
    const popupContent = `
      <div style="min-width: 200px;">
        <div style="font-weight: bold; font-size: 14px; margin-bottom: 5px; color: #1a1a1a;">
          ${icon} ${zona.Nombre}
        </div>
        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">
          <strong>Tipo:</strong> ${zona.TipoZona}
        </div>
        <div style="font-size: 12px; color: #666; margin-bottom: 3px;">
          <strong>Geometría:</strong> ${zona.TipoGeometria}
          ${zona.TipoGeometria === 'Circulo' ? ` (${zona.Radio}m)` : ''}
        </div>
        ${zona.Descripcion ? `
          <div style="font-size: 12px; color: #666; margin-top: 5px; padding-top: 5px; border-top: 1px solid #eee;">
            ${zona.Descripcion}
          </div>
        ` : ''}
      </div>
    `
    
    shape.bindPopup(popupContent)
    shape.addTo(map!)
    zoneShapes.push(shape)
  })
}

const getZoneColor = (tipoZona: string): string => {
  switch (tipoZona) {
    case 'Checkpoint':
      return '#3b82f6' // Blue
    case 'Zona Permitida':
      return '#10b981' // Green
    case 'Zona Restringida':
      return '#ef4444' // Red
    default:
      return '#6b7280' // Gray
  }
}

const getZoneIcon = (tipoZona: string): string => {
  switch (tipoZona) {
    case 'Checkpoint':
      return '📍'
    case 'Zona Permitida':
      return '✅'
    case 'Zona Restringida':
      return '🚫'
    default:
      return '📌'
  }
}


const refreshLocations = async () => {
  await loadData()
}

const updateDeviceFromWebSocket = (ubicacion: any) => {
  // Verificar que tenemos datos válidos
  if (!ubicacion?.dispositivo || !ubicacion.Latitud || !ubicacion.Longitud) {
    return
  }

  // Buscar el dispositivo en la lista actual
  const deviceIndex = devices.value.findIndex(
    d => d.device_id === ubicacion.dispositivo.DispositivoID
  )

  // Crear objeto device actualizado
  const updatedDevice: Device = {
    device_id: ubicacion.dispositivo.DispositivoID,
    device_name: ubicacion.dispositivo.Nombre,
    user_name: ubicacion.empleado?.Nombre || 'Usuario desconocido',
    latitude: ubicacion.Latitud,
    longitude: ubicacion.Longitud,
    minutes_ago: 0 // Recién actualizado
  }

  // Actualizar o agregar el dispositivo
  if (deviceIndex !== -1) {
    devices.value[deviceIndex] = updatedDevice
  } else {
    devices.value.push(updatedDevice)
  }

  // Actualizar marcadores en el mapa
  updateSingleMarker(updatedDevice)
  
  // Actualizar stats
  stats.value.activeDevices = devices.value.length
  stats.value.moving = devices.value.filter(d => d.minutes_ago < 5).length
}

const updateSingleMarker = (device: Device) => {
  if (!map) return

  // Buscar marcador existente por contenido del popup
  const existingMarkerIndex = markers.findIndex(m => {
    const content = m.getPopup()?.getContent()
    return typeof content === 'string' && content.includes(device.device_name)
  })

  // Remover marcador anterior si existe
  if (existingMarkerIndex !== -1) {
    markers[existingMarkerIndex].remove()
    markers.splice(existingMarkerIndex, 1)
  }

  // Crear nuevo marcador
  const icon = L.divIcon({
    html: `<div class="custom-marker ${getMarkerColor(device.minutes_ago)}">
    </div>`,
    className: '',
    iconSize: [30, 30]
  })
  
  const marker = L.marker([device.latitude, device.longitude], { icon })
    .bindPopup(`
      <div class="text-dark">
        <strong>${device.device_name}</strong><br>
        ${device.user_name}<br>
        <small>${formatTimeAgo(device.minutes_ago)}</small>
      </div>
    `)
    .addTo(map!)
  
  markers.push(marker)
}

const centerMap = (device: Device) => {
  if (map) {
    map.setView([device.latitude, device.longitude], 16)
  }
}

const getMarkerColor = (minutesAgo: number) => {
  if (minutesAgo < 5) return 'marker-green'
  if (minutesAgo < 15) return 'marker-yellow'
  return 'marker-red'
}

const formatTimeAgo = (minutes: number): string => {
  if (minutes < 1) {
    const seconds = Math.round(minutes * 60)
    return seconds <= 1 ? 'Justo ahora' : `Hace ${seconds} seg`
  } else if (minutes < 60) {
    const mins = Math.round(minutes)
    return `Hace ${mins} min`
  } else if (minutes < 1440) { // menos de 24 horas
    const hours = Math.round(minutes / 60)
    return `Hace ${hours}h`
  } else {
    const days = Math.round(minutes / 1440)
    return `Hace ${days}d`
  }
}
</script>

<style scoped>
/* Custom Markers */
:deep(.custom-marker) {
  @apply w-8 h-8 rounded-full flex items-center justify-center text-white text-xl;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.4), 0 0 0 3px rgba(255, 255, 255, 0.3);
  transition: transform 0.2s ease;
}

:deep(.custom-marker:hover) {
  transform: scale(1.15);
}

:deep(.marker-green) {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

:deep(.marker-yellow) {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

:deep(.marker-red) {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

/* Animación de entrada */
.stat-card {
  animation: fadeInUp 0.5s ease-out backwards;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
