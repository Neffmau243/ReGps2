```
<template>
  <div class="historial-view">
    <div class="container py-8">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
          <div>
            <h1 class="text-2xl font-bold text-white">Historial de Rutas</h1>
            <p class="text-gray-500 text-xs">Visualiza el recorrido histórico</p>
          </div>
        </div>
      </div>
      
      <!-- Filters -->
      <div class="bg-dark-100 rounded-xl border border-primary/20 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Dispositivo</label>
            <select v-model="filters.deviceId" class="input-compact">
              <option value="">Seleccionar</option>
              <option v-for="device in dispositivos" :key="device.DispositivoID" :value="device.DispositivoID">
                {{ device.Modelo }} - {{ device.IMEI }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Desde</label>
            <input v-model="filters.startDate" type="date" class="input-compact" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">Hasta</label>
            <input v-model="filters.endDate" type="date" class="input-compact" />
          </div>
          
          <div class="flex items-end">
            <button 
              @click="loadHistory"
              :disabled="!filters.deviceId || loading"
              class="btn-primary-compact w-full"
            >
              Buscar
            </button>
          </div>
        </div>
      </div>
      
      <!-- Results -->
      <div v-if="historyData" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Map -->
        <div class="lg:col-span-2">
          <div class="bg-dark-100 rounded-xl border border-primary/20 overflow-hidden">
            <div class="p-4 border-b border-primary/20">
              <h2 class="text-xl font-bold text-white flex items-center">
                Ruta Recorrida
              </h2>
            </div>
            <div id="historyMap" class="h-[600px] bg-dark"></div>
          </div>
        </div>
        
        <!-- Stats -->
        <div class="lg:col-span-1 space-y-6">
          <!-- Device Info -->
          <div class="bg-dark-100 rounded-xl border border-primary/20 p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center">
              Información del Dispositivo
            </h3>
            <div class="space-y-3">
              <div>
                <p class="text-gray-400 text-sm">Dispositivo</p>
                <p class="text-white font-medium">{{ historyData.device.name }}</p>
              </div>
              <div>
                <p class="text-gray-400 text-sm">Usuario</p>
                <p class="text-white font-medium">{{ historyData.device.user_name }}</p>
              </div>
            </div>
          </div>
          
          <!-- Statistics -->
          <div class="bg-dark-100 rounded-xl border border-primary/20 p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
              <i class="bi bi-bar-chart-fill text-primary"></i>
              Estadísticas
            </h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <i class="bi bi-geo-fill text-info"></i>
                  <span class="text-gray-400">Puntos</span>
                </div>
                <span class="text-white font-bold">{{ historyData.statistics.total_points }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <i class="bi bi-signpost-2-fill text-success"></i>
                  <span class="text-gray-400">Distancia</span>
                </div>
                <span class="text-white font-bold">{{ historyData.statistics.distance_km }} km</span>
              </div>
              
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                  <i class="bi bi-clock-fill text-warning"></i>
                  <span class="text-gray-400">Duración</span>
                </div>
                <span class="text-white font-bold">{{ formatDuration(historyData.statistics.duration_minutes) }}</span>
              </div>
            </div>
          </div>
          
          <!-- Timeline -->
          <div class="bg-dark-100 rounded-xl border border-primary/20 p-6">
            <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
              <i class="bi bi-clock-history text-primary"></i>
              Línea de Tiempo
            </h3>
            <div class="space-y-3 max-h-[300px] overflow-y-auto scrollbar-thin">
              <div 
                v-for="(location, index) in historyData.locations.slice(0, 10)" 
                :key="index"
                class="flex items-start space-x-3"
              >
                <div class="w-2 h-2 bg-primary rounded-full mt-2"></div>
                <div>
                  <p class="text-white text-sm">{{ formatTime(location.timestamp) }}</p>
                  <p class="text-gray-400 text-xs">{{ location.latitude.toFixed(4) }}, {{ location.longitude.toFixed(4) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-else class="bg-dark-100 rounded-xl border border-primary/20 p-12 text-center">
        <h3 class="text-xl font-bold text-white mb-2">Selecciona un dispositivo</h3>
        <p class="text-gray-400">Elige un dispositivo y rango de fechas para ver el historial</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

interface Dispositivo {
  DispositivoID: number
  Modelo?: string
  IMEI: string
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

const dispositivos = ref<Dispositivo[]>([])
const zonas = ref<Zona[]>([])
const filters = ref({
  deviceId: '',
  startDate: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  endDate: new Date().toISOString().split('T')[0]
})
const historyData = ref<any>(null)
const loading = ref(false)
let map: L.Map | null = null
let zoneShapes: (L.Circle | L.Polygon)[] = []

onMounted(async () => {
  await loadDevices()
  await loadZones()
})

const loadDevices = async () => {
  try {
    const response = await api.get('/dispositivos')
    dispositivos.value = response.data
  } catch (error) {
    console.error('Error loading devices:', error)
  }
}

const loadZones = async () => {
  try {
    const response = await api.get('/zonas')
    zonas.value = response.data
  } catch (error) {
    console.error('Error loading zones:', error)
  }
}

const loadHistory = async () => {
  if (!filters.value.deviceId) return
  
  loading.value = true
  
  try {
    const response = await api.get('/locations/history', {
      params: {
        device_id: filters.value.deviceId,
        start_date: filters.value.startDate,
        end_date: filters.value.endDate
      }
    })
    
    historyData.value = response.data
    
    // Wait for DOM update
    setTimeout(() => {
      initMap()
      drawRoute()
      drawZones()
    }, 100)
    
  } catch (error) {
    console.error('Error loading history:', error)
  } finally {
    loading.value = false
  }
}

const initMap = () => {
  if (map) {
    map.remove()
  }
  
  map = L.map('historyMap').setView([-12.0464, -77.0428], 13)
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map)
}

const drawRoute = () => {
  if (!map || !historyData.value) return
  
  const locations = historyData.value.locations
  if (locations.length === 0) return
  
  // Draw route line
  const latlngs = locations.map((loc: any) => [loc.latitude, loc.longitude])
  L.polyline(latlngs, { color: '#FF6B35', weight: 3 }).addTo(map!)
  
  // Add start marker (RED = Inicio)
  const startIcon = L.divIcon({
    html: '<div class="custom-marker bg-red-500"></div>',
    className: '',
    iconSize: [30, 30]
  })
  L.marker([locations[0].latitude, locations[0].longitude], { icon: startIcon })
    .bindPopup('Inicio')
    .addTo(map!)
  
  // Add end marker (GREEN = Fin)
  const endIcon = L.divIcon({
    html: '<div class="custom-marker bg-green-500"></div>',
    className: '',
    iconSize: [30, 30]
  })
  const lastLoc = locations[locations.length - 1]
  L.marker([lastLoc.latitude, lastLoc.longitude], { icon: endIcon })
    .bindPopup('Fin')
    .addTo(map!)
  
  // Fit bounds
  const bounds = L.latLngBounds(latlngs)
  map!.fitBounds(bounds, { padding: [50, 50] })
}

const drawZones = () => {
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

const formatTime = (timestamp: string) => {
  return new Date(timestamp).toLocaleTimeString('es-ES', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDuration = (minutes: number) => {
  if (minutes < 60) {
    return `${Math.round(minutes)} min`
  } else if (minutes < 1440) {
    // Menos de 24 horas - mostrar en horas y minutos
    const hours = Math.floor(minutes / 60)
    const mins = Math.round(minutes % 60)
    return mins > 0 ? `${hours}h ${mins}min` : `${hours}h`
  } else {
    // 24 horas o más - mostrar en días y horas
    const days = Math.floor(minutes / 1440)
    const hours = Math.floor((minutes % 1440) / 60)
    return hours > 0 ? `${days}d ${hours}h` : `${days}d`
  }
}
</script>

<style scoped>
.input-compact {
  width: 100%;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  color: #ffffff;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.input-compact:focus {
  outline: none;
  border-color: #FF6B35;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.input-compact option {
  background: #1f2937;
  color: #ffffff;
  padding: 12px;
}

.btn-primary-compact {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 10px 16px;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8C5E 100%);
  color: white;
  font-weight: 600;
  font-size: 14px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(255, 107, 53, 0.25);
}

.btn-primary-compact:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.35);
}

.btn-primary-compact:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

:deep(.custom-marker) {
  @apply w-8 h-8 rounded-full flex items-center justify-center text-white text-xl shadow-lg;
}
</style>
```
