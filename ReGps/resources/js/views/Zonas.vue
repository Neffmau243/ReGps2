<template>
  <div class="zonas-view">
    <div class="container py-8">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div>
              <h1 class="text-2xl font-bold text-white">Zonas de Geofencing</h1>
              <p class="text-gray-500 text-xs">{{ zonas.length }} zonas configuradas</p>
            </div>
          </div>
          <router-link 
            v-if="authStore.isAdmin"
            to="/zonas/crear" 
            class="btn-primary-compact"
          >
            <i class="bi bi-geo-alt-fill"></i> Nueva
          </router-link>
        </div>

        <!-- Compact Filters -->
        <div class="flex items-center gap-4 flex-wrap">
          <div class="relative flex-1 max-w-xs">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input 
              v-model="searchQuery"
              type="text" 
              placeholder="Buscar zona..."
              class="input-compact pl-10"
            />
          </div>
          <select v-model="filterType" class="input-compact">
            <option value="">Todas</option>
            <option value="Checkpoint">Checkpoint</option>
            <option value="Zona Permitida">Zona Permitida</option>
            <option value="Zona Restringida">Zona Restringida</option>
          </select>
          <select v-model="filterStatus" class="input-compact">
            <option value="">Estado</option>
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
          </select>
        </div>
      </div>
      
      <!-- Zones Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="zona in filteredZonas" 
          :key="zona.ZonaID"
          class="zone-card"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-start space-x-3">
              <div :class="getZoneIconClass(zona.TipoZona)">
                <i :class="getZoneIcon(zona.TipoZona)"></i>
              </div>
              <div>
                <h3 class="text-lg font-bold text-white">{{ zona.Nombre }}</h3>
                <p class="text-gray-400 text-sm">{{ zona.TipoZona }}</p>
              </div>
            </div>
            <span :class="getStatusBadge(zona.Estado)">
              {{ zona.Estado }}
            </span>
          </div>
          
          <div class="space-y-4 mb-6">
            <div class="flex items-center text-sm gap-8">
              <i class="bi bi-diagram-3-fill text-gray-400 text-lg w-5"></i>
              <span class="text-gray-300">{{ zona.TipoGeometria }}</span>
            </div>
            
            <div v-if="zona.Radio" class="flex items-center text-sm gap-8">
              <i class="bi bi-circle text-gray-400 text-lg w-5"></i>
              <span class="text-gray-300">Radio: {{ zona.Radio }}m</span>
            </div>
            
            <div v-if="zona.HorarioInicio && zona.HorarioFin" class="flex items-center text-sm gap-8">
              <i class="bi bi-clock-fill text-gray-400 text-lg w-5"></i>
              <span class="text-gray-300">{{ zona.HorarioInicio }} - {{ zona.HorarioFin }}</span>
            </div>
          </div>
          
          <p v-if="zona.Descripcion" class="text-gray-400 text-sm mb-6 leading-relaxed">
            {{ zona.Descripcion }}
          </p>
          
          <div class="flex items-center justify-end gap-4 pt-5 border-t border-primary/20">
            <router-link 
              :to="`/zonas/editar/${zona.ZonaID}`"
              class="action-btn edit"
              title="Editar"
            >
              <i class="bi bi-pencil-fill"></i>
            </router-link>
            <button 
              v-if="authStore.isAdmin"
              @click="deleteZone(zona.ZonaID)"
              class="action-btn delete"
              title="Eliminar"
            >
              <i class="bi bi-trash-fill"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <p class="text-gray-400">Cargando zonas...</p>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="filteredZonas.length === 0 && !loading" class="bg-dark-100 rounded-xl border border-primary/20 p-12 text-center">
        <h3 class="text-xl font-bold text-white mb-2">No hay zonas</h3>
        <p class="text-gray-400 mb-6">Crea tu primera zona de geofencing</p>
        <router-link 
          v-if="authStore.isAdmin"
          to="/zonas/crear" 
          class="btn-primary inline-flex items-center"
        >
          <i class="bi bi-geo-alt-fill"></i>
          Nueva Zona
        </router-link>
      </div>
    </div>
    
    <!-- Map Modal -->
    <div v-if="showMapModal" class="fixed inset-0 bg-black/80 flex items-center justify-center z-50 p-4">
      <div class="bg-dark-100 rounded-xl border border-primary/20 w-full max-w-4xl">
        <div class="p-4 border-b border-primary/20 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white">{{ selectedZone?.Nombre }}</h3>
          <button @click="showMapModal = false" class="text-gray-400 hover:text-white">
          </button>
        </div>
        <div id="zoneMap" class="h-[500px]"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

interface Zona {
  ZonaID: number
  Nombre: string
  TipoZona: string
  TipoGeometria: string
  Latitud: number
  Longitud: number
  Radio?: number
  Coordenadas?: any
  HorarioInicio?: string
  HorarioFin?: string
  Descripcion?: string
  Estado: string
}

const authStore = useAuthStore()
const zonas = ref<Zona[]>([])
const loading = ref(true)
const searchQuery = ref('')
const filterType = ref('')
const filterStatus = ref('')
const showMapModal = ref(false)
const selectedZone = ref<Zona | null>(null)
let zoneMap: L.Map | null = null

const filteredZonas = computed(() => {
  return zonas.value.filter(zona => {
    const matchesSearch = zona.Nombre.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesType = !filterType.value || zona.TipoZona === filterType.value
    const matchesStatus = !filterStatus.value || zona.Estado === filterStatus.value
    return matchesSearch && matchesType && matchesStatus
  })
})

onMounted(async () => {
  await loadZonas()
})

const loadZonas = async () => {
  loading.value = true
  try {
    const response = await api.get('/zonas')
    zonas.value = response.data
  } catch (error) {
    console.error('Error loading zones:', error)
  } finally {
    loading.value = false
  }
}

const getZoneIcon = (tipo: string) => {
  switch (tipo) {
    case 'Checkpoint': return 'bi bi-flag-fill'
    case 'Zona Permitida': return 'bi bi-check-circle-fill'
    case 'Zona Restringida': return 'bi bi-x-circle-fill'
    default: return 'bi bi-geo-alt-fill'
  }
}

const getZoneIconClass = (tipo: string) => {
  const base = 'w-10 h-10 rounded-lg flex items-center justify-center text-xl'
  switch (tipo) {
    case 'Checkpoint': return `${base} bg-primary/20 text-primary`
    case 'Zona Permitida': return `${base} bg-green-500/20 text-green-500`
    case 'Zona Restringida': return `${base} bg-red-500/20 text-red-500`
    default: return `${base} bg-primary/20 text-primary`
  }
}

const getStatusBadge = (estado: string) => {
  return estado === 'Activo' 
    ? 'px-2 py-1 bg-green-500/20 text-green-500 text-xs rounded-full'
    : 'px-2 py-1 bg-primary/10 text-gray-400 text-xs rounded-full'
}

const viewOnMap = (zona: Zona) => {
  selectedZone.value = zona
  showMapModal.value = true
  
  setTimeout(() => {
    initZoneMap(zona)
  }, 100)
}

const initZoneMap = (zona: Zona) => {
  if (zoneMap) {
    zoneMap.remove()
  }
  
  zoneMap = L.map('zoneMap').setView([zona.Latitud, zona.Longitud], 15)
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(zoneMap)
  
  // Draw zone
  if (zona.TipoGeometria === 'Circulo' && zona.Radio) {
    L.circle([zona.Latitud, zona.Longitud], {
      radius: zona.Radio,
      color: '#0066FF',
      fillColor: '#0066FF',
      fillOpacity: 0.2
    }).addTo(zoneMap)
  } else if (zona.TipoGeometria === 'Poligono' && zona.Coordenadas) {
    const coords = JSON.parse(zona.Coordenadas)
    const latlngs = coords.map((c: any) => [c.lat, c.lng])
    L.polygon(latlngs, {
      color: '#0066FF',
      fillColor: '#0066FF',
      fillOpacity: 0.2
    }).addTo(zoneMap)
  }
  
  // Add marker
  L.marker([zona.Latitud, zona.Longitud])
    .bindPopup(zona.Nombre)
    .addTo(zoneMap)
}

const deleteZone = async (id: number) => {
  if (!confirm('¿Estás seguro de eliminar esta zona?')) return
  
  try {
    await api.delete(`/zonas/${id}`)
    await loadZonas()
  } catch (error) {
    console.error('Error deleting zone:', error)
    alert('Error al eliminar la zona')
  }
}
</script>

<style scoped>
.input-compact {
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  color: #ffffff;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  min-width: 140px;
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
  padding: 8px 16px;
  background: linear-gradient(135deg, #FF6B35 0%, #FF8C5E 100%);
  color: white;
  font-weight: 600;
  font-size: 13px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(255, 107, 53, 0.25);
}

.btn-primary-compact:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.35);
}

/* Zone Card - Specific to Zonas view */
.zone-card {
  background: transparent;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid rgba(255, 107, 53, 0.2);
  transition: all 0.3s ease;
  position: relative;
}

.zone-card::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: linear-gradient(180deg, #FF6B35 0%, #FF8C5E 100%);
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 12px 0 0 12px;
}

.zone-card:hover {
  background: rgba(255, 107, 53, 0.05);
  border-color: rgba(255, 107, 53, 0.4);
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
}

.zone-card:hover::before {
  opacity: 1;
}

.input-field {
  @apply w-full px-4 py-2 bg-dark border border-primary/20 rounded-lg text-white focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors;
}

.btn-primary {
  @apply px-6 py-2 bg-primary hover:bg-primary-600 text-white font-medium rounded-lg transition-all;
}
</style>
```
