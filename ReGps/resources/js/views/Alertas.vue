<template>
  <div class="alertas-view">
    <div class="container py-8">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div>
              <h1 class="text-2xl font-bold text-white">Alertas del Sistema</h1>
              <p class="text-gray-500 text-xs">{{ alertas.length }} alertas registradas</p>
            </div>
          </div>
        </div>

        <!-- Inline Stats + Filters -->
        <div class="flex items-center gap-8 flex-wrap">
          <!-- Mini Stats -->
          <div class="flex items-center gap-6">
            <div class="stat-mini">
              <span class="text-white font-semibold text-sm">{{ pendingCount }}</span>
              <span class="text-gray-500 text-xs">Pendientes</span>
            </div>
            <div class="stat-mini">
              <span class="text-white font-semibold text-sm">{{ resolvedCount }}</span>
              <span class="text-gray-500 text-xs">Resueltas</span>
            </div>
            <div class="stat-mini">
              <span class="text-white font-semibold text-sm">{{ criticalCount }}</span>
              <span class="text-gray-500 text-xs">Críticas</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="h-8 w-px bg-gray-700"></div>

          <!-- Compact Filters -->
          <div class="flex items-center gap-4 flex-1">
            <div class="relative">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Buscar..."
                class="input-compact pl-10"
              />
            </div>
            <select v-model="filterType" class="input-compact">
              <option value="">Todas</option>
              <option value="Velocidad">Velocidad</option>
              <option value="Zona">Zona</option>
              <option value="Dispositivo">Dispositivo</option>
            </select>
            <select v-model="filterPriority" class="input-compact">
              <option value="">Prioridad</option>
              <option value="Baja">Baja</option>
              <option value="Media">Media</option>
              <option value="Alta">Alta</option>
              <option value="Crítica">Crítica</option>
            </select>
            <select v-model="filterStatus" class="input-compact">
              <option value="">Estado</option>
              <option value="Pendiente">Pendiente</option>
              <option value="Resuelta">Resuelta</option>
            </select>
            <button
              @click="loadAlertas"
              class="btn-primary-compact"
              title="Actualizar"
            >
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Alerts List -->
      <div class="space-y-4">
        <div 
          v-for="alerta in filteredAlertas" 
          :key="alerta.AlertaID"
          class="alert-card"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-start flex-1">
              <!-- Content -->
              <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                  <h3 class="text-lg font-bold text-white">{{ alerta.TipoAlerta }}</h3>
                  <span :class="getPriorityBadge(alerta.Prioridad)">
                    {{ alerta.Prioridad }}
                  </span>
                  <span :class="getStatusBadge(alerta.Estado)">
                    {{ alerta.Estado }}
                  </span>
                </div>
                
                <p class="text-gray-300 mb-3">{{ alerta.Mensaje }}</p>
                
                <div class="flex items-center space-x-6 text-sm text-gray-400">
                  <div class="flex items-center space-x-2">
                    <i class="bi bi-phone-fill"></i>
                    <span>Dispositivo #{{ alerta.DispositivoID }}</span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <i class="bi bi-clock-fill"></i>
                    <span>{{ formatDate(alerta.FechaHora) }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-2">
              <button 
                v-if="alerta.Estado === 'Pendiente'"
                @click="resolveAlert(alerta.AlertaID)"
                class="action-btn toggle"
                title="Resolver"
              >
                <i class="bi bi-check-circle-fill"></i>
              </button>
              <button
                @click="viewDetails(alerta)"
                class="action-btn"
                title="Ver detalles"
              >
                <i class="bi bi-eye-fill"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="filteredAlertas.length === 0" class="bg-dark-100 rounded-xl border border-primary/20 p-12 text-center">
        <h3 class="text-xl font-bold text-white mb-2">No hay alertas</h3>
        <p class="text-gray-400">No se encontraron alertas con los filtros seleccionados</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

interface Alerta {
  AlertaID: number
  DispositivoID: number
  TipoAlerta: string
  Mensaje: string
  Prioridad: string
  Estado: string
  FechaHora: string
}

const alertas = ref<Alerta[]>([])
const searchQuery = ref('')
const filterType = ref('')
const filterPriority = ref('')
const filterStatus = ref('')

const filteredAlertas = computed(() => {
  return alertas.value.filter(alerta => {
    const query = searchQuery.value.toLowerCase()
    const matchesSearch = !query || 
                         alerta.TipoAlerta.toLowerCase().includes(query) ||
                         alerta.Mensaje.toLowerCase().includes(query) ||
                         alerta.DispositivoID.toString().includes(query)
    const matchesType = !filterType.value || alerta.TipoAlerta === filterType.value
    const matchesPriority = !filterPriority.value || alerta.Prioridad === filterPriority.value
    const matchesStatus = !filterStatus.value || alerta.Estado === filterStatus.value
    return matchesSearch && matchesType && matchesPriority && matchesStatus
  })
})

const pendingCount = computed(() => 
  alertas.value.filter(a => a.Estado === 'Pendiente').length
)

const resolvedCount = computed(() => 
  alertas.value.filter(a => a.Estado === 'Resuelta').length
)

const criticalCount = computed(() => 
  alertas.value.filter(a => a.Prioridad === 'Crítica' && a.Estado === 'Pendiente').length
)

onMounted(async () => {
  await loadAlertas()
})

const loadAlertas = async () => {
  try {
    const response = await api.get('/alertas')
    alertas.value = response.data
  } catch (error) {
    console.error('Error loading alerts:', error)
  }
}

const getPriorityBadge = (prioridad: string) => {
  const base = 'px-2 py-1 text-xs rounded-full font-medium'
  switch (prioridad) {
    case 'Crítica': return `${base} bg-red-500/20 text-red-500`
    case 'Alta': return `${base} bg-primary/20 text-primary`
    case 'Media': return `${base} bg-primary/15 text-primary`
    default: return `${base} bg-primary/10 text-gray-400`
  }
}

const getStatusBadge = (estado: string) => {
  return estado === 'Pendiente'
    ? 'px-2 py-1 bg-red-500/20 text-red-500 text-xs rounded-full'
    : 'px-2 py-1 bg-green-500/20 text-green-500 text-xs rounded-full'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('es-ES', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const resolveAlert = async (id: number) => {
  try {
    await api.put(`/alertas/${id}`, { Estado: 'Resuelta' })
    await loadAlertas()
  } catch (error) {
    console.error('Error resolving alert:', error)
    alert('Error al resolver la alerta')
  }
}

const viewDetails = (alerta: Alerta) => {
  alert(`Detalles de la alerta:\n\n${JSON.stringify(alerta, null, 2)}`)
}
</script>

<style scoped>
/* Alert Card Specific Styles */
.alert-card {
  background: transparent;
  border: 1px solid rgba(255, 107, 53, 0.2);
  border-radius: 12px;
  padding: 24px;
  transition: all 0.3s ease;
  position: relative;
}

.alert-card::before {
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

.alert-card:hover {
  background: rgba(255, 107, 53, 0.05);
  border-color: rgba(255, 107, 53, 0.4);
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
}

.alert-card:hover::before {
  opacity: 1;
}
</style>
