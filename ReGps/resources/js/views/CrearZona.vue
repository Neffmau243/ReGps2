<template>
  <div class="crear-zona-view">
    <div class="container py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-white mb-2">
          {{ isEditing ? 'Editar Zona' : 'Nueva Zona' }}
        </h1>
        <p class="text-gray-400">{{ isEditing ? 'Modifica' : 'Crea' }} una zona de geofencing</p>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-1">
          <form @submit.prevent="handleSubmit" class="bg-dark-100 rounded-xl border border-primary/20 p-6 space-y-6">
            <!-- Nombre -->
            <div>
              <label class="label">
                <i class="bi bi-geo-alt-fill"></i>
                Nombre de la Zona *
              </label>
              <input 
                v-model="form.Nombre"
                type="text" 
                required
                class="input-field"
                placeholder="Ej: Oficina Central"
              />
            </div>
            
            <!-- Tipo de Zona -->
            <div>
              <label class="label">
                <i class="bi bi-tag-fill"></i>
                Tipo de Zona *
              </label>
              <select v-model="form.TipoZona" required class="input-field">
                <option value="">Seleccionar</option>
                <option value="Checkpoint">Checkpoint</option>
                <option value="Zona Permitida">Zona Permitida</option>
                <option value="Zona Restringida">Zona Restringida</option>
              </select>
            </div>
            
            <!-- Tipo de Geometría -->
            <div>
              <label class="label">Tipo de Geometría *</label>
              <select v-model="form.TipoGeometria" required class="input-field">
                <option value="">Seleccionar</option>
                <option value="Circulo">Círculo</option>
                <option value="Poligono">Polígono</option>
              </select>
            </div>
            
            <!-- Radio (solo para círculo) -->
            <div v-if="form.TipoGeometria === 'Circulo'">
              <label class="label">Radio (metros) *</label>
              <input 
                v-model.number="form.Radio"
                type="number" 
                min="10"
                step="10"
                required
                class="input-field"
                placeholder="500"
              />
            </div>
            
            <!-- Checkpoint Permanente -->
            <div v-if="form.TipoZona === 'Checkpoint'" class="bg-dark-100 border border-primary/20 rounded-lg p-4">
              <label class="flex items-center gap-3 cursor-pointer">
                <input 
                  v-model="isPermanent"
                  type="checkbox" 
                  class="w-5 h-5 rounded border-primary/30 text-primary focus:ring-primary focus:ring-offset-0 bg-dark cursor-pointer"
                />
                <span class="text-gray-300 font-medium">
                  <i class="bi bi-infinity text-primary mr-2"></i>
                  Checkpoint Permanente (24/7)
                </span>
              </label>
              <p class="text-gray-400 text-xs mt-2 ml-8">Si está activo, no se requieren horarios</p>
            </div>
            
            <!-- Horario -->
            <div v-if="!isPermanent" class="grid grid-cols-2 gap-4">
              <div>
                <label class="label">
                  <i class="bi bi-clock-fill"></i>
                  Hora Inicio
                </label>
                <input 
                  v-model="form.HorarioInicio"
                  type="time" 
                  class="input-field"
                  placeholder="08:00"
                />
              </div>
              <div>
                <label class="label">
                  <i class="bi bi-clock-fill"></i>
                  Hora Fin
                </label>
                <input 
                  v-model="form.HorarioFin"
                  type="time" 
                  class="input-field"
                  placeholder="18:00"
                />
              </div>
            </div>
            
            <!-- Descripción -->
            <div>
              <label class="label">Descripción</label>
              <textarea 
                v-model="form.Descripcion"
                rows="3"
                class="input-field"
                placeholder="Descripción opcional..."
              ></textarea>
            </div>
            
            <!-- Estado -->
            <div>
              <label class="label">Estado</label>
              <select v-model="form.Estado" class="input-field">
                <option value="Activo">Activo</option>
                <option value="Inactivo">Inactivo</option>
              </select>
            </div>
            
            <!-- Instructions -->
            <div class="bg-primary/10 border border-primary/30 rounded-lg p-4">
              <p class="text-primary text-sm font-medium mb-2">
                Instrucciones
              </p>
              <ul class="text-gray-300 text-xs space-y-1">
                <li v-if="form.TipoGeometria === 'Circulo'">
                  • Haz clic en el mapa para colocar el centro
                </li>
                <li v-if="form.TipoGeometria === 'Poligono'">
                  • Haz clic en el mapa para agregar puntos
                </li>
                <li v-if="form.TipoGeometria === 'Poligono'">
                  • Doble clic para finalizar el polígono
                </li>
              </ul>
            </div>
            
            <!-- Buttons -->
            <div class="flex space-x-3">
              <button 
                type="submit"
                :disabled="!canSubmit || loading"
                class="btn-primary flex-1"
              >
                <span v-if="!loading" class="flex items-center justify-center gap-2">
                  <i :class="isEditing ? 'bi bi-check-circle-fill' : 'bi bi-plus-circle-fill'"></i>
                  {{ isEditing ? 'Actualizar Zona' : 'Crear Zona' }}
                </span>
                <span v-else class="flex items-center justify-center gap-2">
                  <i class="bi bi-arrow-repeat animate-spin"></i>
                  Guardando...
                </span>
              </button>
              <router-link to="/zonas" class="btn-secondary flex items-center justify-center gap-2">
                <i class="bi bi-x-circle-fill"></i>
                Cancelar
              </router-link>
            </div>
          </form>
        </div>
        
        <!-- Map -->
        <div class="lg:col-span-2">
          <div class="bg-dark-100 rounded-xl border border-primary/20 overflow-hidden sticky top-8">
            <div class="p-4 border-b border-primary/20">
              <h2 class="text-xl font-bold text-white flex items-center">
                <i class="bi bi-map-fill mr-2 text-primary"></i>
                Mapa Interactivo
              </h2>
              <p class="text-gray-400 text-sm mt-1">
                {{ form.TipoGeometria === 'Circulo' ? 'Haz clic para colocar el centro' : form.TipoGeometria === 'Poligono' ? 'Haz clic para agregar puntos' : 'Selecciona un tipo de geometría' }}
              </p>
            </div>
            <div id="createMap" class="map-container"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// Arreglar el problema de los iconos de Leaflet
delete (L.Icon.Default.prototype as any)._getIconUrl
L.Icon.Default.mergeOptions({
  iconUrl: '/images/marker-icon.png',
  iconRetinaUrl: '/images/marker-icon-2x.png',
  shadowUrl: '/images/marker-shadow.png',
})

const route = useRoute()
const router = useRouter()
const isEditing = computed(() => !!route.params.id)
const isPermanent = ref(false)

const form = ref({
  Nombre: '',
  TipoZona: '',
  TipoGeometria: '',
  Latitud: -12.0464,
  Longitud: -77.0428,
  Radio: 500,
  Coordenadas: null as any,
  HorarioInicio: '',
  HorarioFin: '',
  Descripcion: '',
  Estado: 'Activo'
})

const loading = ref(false)
let map: L.Map | null = null
let currentShape: L.Circle | L.Polygon | null = null
let currentMarker: L.Marker | null = null
let polygonPoints: L.LatLng[] = []

const canSubmit = computed(() => {
  if (!form.value.Nombre || !form.value.TipoZona || !form.value.TipoGeometria) {
    return false
  }
  
  if (form.value.TipoGeometria === 'Circulo') {
    return !!form.value.Radio && form.value.Latitud && form.value.Longitud
  }
  
  if (form.value.TipoGeometria === 'Poligono') {
    return !!form.value.Coordenadas
  }
  
  return false
})

onMounted(async () => {
  // Dar tiempo para que el DOM se renderice
  await new Promise(resolve => setTimeout(resolve, 100))
  initMap()
  
  if (isEditing.value) {
    await loadZone()
  }
})

const initMap = () => {
  const mapElement = document.getElementById('createMap')
  if (!mapElement) {
    console.error('Map element not found')
    return
  }
  
  map = L.map('createMap').setView([form.value.Latitud, form.value.Longitud], 13)
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map)
  
  // Forzar el redimensionamiento del mapa
  setTimeout(() => {
    if (map) {
      map.invalidateSize()
    }
  }, 200)
  
  // Map click handler
  map.on('click', handleMapClick)
}

const handleMapClick = (e: L.LeafletMouseEvent) => {
  if (form.value.TipoGeometria === 'Circulo') {
    form.value.Latitud = e.latlng.lat
    form.value.Longitud = e.latlng.lng
    drawCircle()
  } else if (form.value.TipoGeometria === 'Poligono') {
    polygonPoints.push(e.latlng)
    drawPolygon()
  }
}

const drawCircle = () => {
  // Eliminar el círculo y marcador anteriores
  if (currentShape) {
    currentShape.remove()
  }
  if (currentMarker) {
    currentMarker.remove()
  }
  
  // Crear nuevo círculo
  currentShape = L.circle([form.value.Latitud, form.value.Longitud], {
    radius: form.value.Radio,
    color: '#FF6B35',
    fillColor: '#FF6B35',
    fillOpacity: 0.2
  }).addTo(map!)
  
  // Crear nuevo marcador
  currentMarker = L.marker([form.value.Latitud, form.value.Longitud]).addTo(map!)
}

const drawPolygon = () => {
  if (currentShape) {
    currentShape.remove()
  }
  
  if (polygonPoints.length > 0) {
    currentShape = L.polygon(polygonPoints, {
      color: '#FF6B35',
      fillColor: '#FF6B35',
      fillOpacity: 0.2
    }).addTo(map!)
    
    // Calculate center
    const bounds = currentShape.getBounds()
    const center = bounds.getCenter()
    form.value.Latitud = center.lat
    form.value.Longitud = center.lng
    
    // Save coordinates
    form.value.Coordenadas = polygonPoints.map(p => ({
      lat: p.lat,
      lng: p.lng
    }))
  }
}

const loadZone = async () => {
  try {
    const response = await api.get(`/zonas/${route.params.id}`)
    const zona = response.data
    
    // Helper function to safely parse JSON
    const parseIfString = (value: any) => {
      if (!value) return null
      if (typeof value === 'string') {
        try {
          return JSON.parse(value)
        } catch (e) {
          return null
        }
      }
      return value // Already an object
    }
    
    form.value = {
      Nombre: zona.Nombre,
      TipoZona: zona.TipoZona,
      TipoGeometria: zona.TipoGeometria,
      Latitud: zona.Latitud,
      Longitud: zona.Longitud,
      Radio: zona.Radio || 500,
      Coordenadas: parseIfString(zona.Coordenadas),
      HorarioInicio: zona.HorarioInicio || '',
      HorarioFin: zona.HorarioFin || '',
      Descripcion: zona.Descripcion || '',
      Estado: zona.Estado
    }
    
    // Draw existing zone
    if (zona.TipoGeometria === 'Circulo') {
      drawCircle()
    } else if (zona.TipoGeometria === 'Poligono' && zona.Coordenadas) {
      const coords = parseIfString(zona.Coordenadas)
      if (coords && Array.isArray(coords)) {
        polygonPoints = coords.map((c: any) => L.latLng(c.lat, c.lng))
        drawPolygon()
      }
    }
    
    // Centrar el mapa y forzar actualización
    if (map) {
      map.setView([zona.Latitud, zona.Longitud], 15)
      setTimeout(() => {
        if (map) {
          map.invalidateSize()
        }
      }, 100)
    }
    
  } catch (error) {
    console.error('Error loading zone:', error)
    alert('Error al cargar la zona')
  }
}

const handleSubmit = async () => {
  loading.value = true
  
  try {
    const data = {
      ...form.value,
      // Si es permanente, limpiar los horarios
      HorarioInicio: isPermanent.value ? null : form.value.HorarioInicio || null,
      HorarioFin: isPermanent.value ? null : form.value.HorarioFin || null,
      Coordenadas: form.value.Coordenadas ? JSON.stringify(form.value.Coordenadas) : null
    }
    
    if (isEditing.value) {
      await api.put(`/zonas/${route.params.id}`, data)
    } else {
      await api.post('/zonas', data)
    }
    
    router.push('/zonas')
  } catch (error: any) {
    console.error('Error saving zone:', error)
    const message = error.response?.data?.message || 'Error al guardar la zona'
    const errors = error.response?.data?.errors
    if (errors) {
      const errorMessages = Object.values(errors).flat().join('\\n')
      alert(`${message}:\\n${errorMessages}`)
    } else {
      alert(message)
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.map-container {
  width: 100%;
  height: calc(100vh - 250px);
  min-height: 500px;
  position: relative;
  z-index: 1;
}

.label {
  @apply block text-sm font-medium text-gray-300 mb-2;
}

.input-field {
  @apply w-full px-4 py-2 bg-dark border border-primary/20 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors;
}

.btn-primary {
  padding: 12px 24px;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8C5E 100%);
  color: white;
  font-weight: 700;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(255, 107, 53, 0.4);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 12px 24px;
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.1);
  color: white;
  font-weight: 600;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
  display: inline-block;
}

.btn-secondary:hover {
  border-color: rgba(255, 107, 53, 0.5);
  background: rgba(255, 107, 53, 0.1);
  transform: translateY(-1px);
}

/* Asegurar que Leaflet funcione correctamente */
:deep(.leaflet-container) {
  width: 100%;
  height: 100%;
  background: #1a1a1a;
}

:deep(.leaflet-tile-pane) {
  filter: brightness(0.9) contrast(1.1);
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
