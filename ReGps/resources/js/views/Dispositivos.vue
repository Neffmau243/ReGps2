<template>
  <div class="dispositivos-view">
    <div class="container py-10 px-6 max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <div>
              <h1 class="text-3xl font-bold text-white">Dispositivos GPS</h1>
              <p class="text-gray-500 text-sm">{{ dispositivos.length }} registrados</p>
            </div>
          </div>
          <button 
            @click="showCreateModal = true"
            class="btn-primary-compact"
          >
            <i class="bi bi-phone-fill"></i> Nuevo
          </button>
        </div>

        <!-- Inline Stats + Filters -->
        <div class="flex items-center gap-8 flex-wrap">
          <!-- Mini Stats -->
          <div class="flex items-center gap-6">
            <div class="stat-mini">
              <span class="text-white font-semibold text-sm">{{ dispositivos.filter(d => d.Estado === 'Activo').length }}</span>
              <span class="text-gray-500 text-xs">Activos</span>
            </div>
            <div class="stat-mini">
              <span class="text-white font-semibold text-sm">{{ dispositivos.filter(d => d.Estado === 'Mantenimiento').length }}</span>
              <span class="text-gray-500 text-xs">Mantenimiento</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="h-8 w-px bg-gray-700"></div>

          <!-- Compact Filters -->
          <div class="flex items-center gap-4 flex-1">
            <div class="relative flex-1 max-w-xs">
              <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
              <input 
                v-model="searchQuery"
                type="text" 
                placeholder="Buscar..."
                class="input-compact pl-10"
              />
            </div>
            <select v-model="filterStatus" class="input-compact">
              <option value="">Todos</option>
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
              <option value="Mantenimiento">Mantenimiento</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Devices Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <div 
          v-for="dispositivo in filteredDispositivos" 
          :key="dispositivo.DispositivoID"
          class="device-card"
        >
          <div class="flex items-start justify-between mb-4">
            <div class="flex items-start space-x-3">
              <div :class="getDeviceIconClass(dispositivo.Estado)">
              </div>
              <div>
                <h3 class="text-lg font-bold text-white">{{ dispositivo.Modelo }}</h3>
                <p class="text-gray-400 text-sm">{{ dispositivo.Marca || 'Sin marca' }}</p>
              </div>
            </div>
            <span class="status-badge" :class="getStatusBadgeClass(dispositivo.Estado)">
              <span class="status-dot"></span>
              {{ dispositivo.Estado }}
            </span>
          </div>
          
          <div class="space-y-2 mb-4">
            <div class="flex items-center text-sm">
              <span class="text-gray-300">IMEI: {{ dispositivo.IMEI }}</span>
            </div>
            
            <div v-if="dispositivo.Marca" class="flex items-center text-sm">
              <span class="text-gray-300">Marca: {{ dispositivo.Marca }}</span>
            </div>
            
            <div v-if="dispositivo.EmpleadoID" class="flex items-center text-sm">
              <span class="text-gray-300">{{ getEmpleadoNombre(dispositivo.EmpleadoID) }}</span>
            </div>
            
            <div class="flex items-center text-sm">
              <span class="text-gray-300">{{ formatDate(dispositivo.created_at || dispositivo.FechaAsignacion) }}</span>
            </div>
          </div>
          
          <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-700">
            <button 
              @click="viewLocation(dispositivo)"
              class="action-btn location"
              title="Ver ubicación"
            >
              <i class="bi bi-geo-alt-fill"></i>
            </button>
            <button 
              @click="editDevice(dispositivo)"
              class="action-btn edit"
              title="Editar"
            >
              <i class="bi bi-pencil-fill"></i>
            </button>
            <button 
              @click="deleteDevice(dispositivo.DispositivoID)"
              class="action-btn delete"
              title="Eliminar"
            >
              <i class="bi bi-trash-fill"></i>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="filteredDispositivos.length === 0" class="bg-dark-100 rounded-xl border border-primary/20 p-12 text-center">
        <h3 class="text-xl font-bold text-white mb-2">No hay dispositivos</h3>
        <p class="text-gray-400 mb-6">Registra tu primer dispositivo GPS</p>
        <button 
          @click="showCreateModal = true"
          class="btn-primary inline-flex items-center"
        >
          Nuevo Dispositivo
        </button>
      </div>
    </div>
    
    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingDevice" class="modal-overlay">
      <div class="modal-container" @click.self="closeModal">
        <div class="modal-content">
        <div class="p-6 border-b border-primary/20 flex items-center justify-between">
          <h3 class="text-xl font-bold text-white flex items-center gap-2">
            <i class="bi" :class="editingDevice ? 'bi-pencil-square' : 'bi-plus-circle-fill'"></i>
            {{ editingDevice ? 'Editar Dispositivo' : 'Nuevo Dispositivo' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-white transition-colors">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        
        <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
          <div>
            <label class="label">
              <i class="bi bi-phone-fill"></i>
              Modelo *
            </label>
            <input 
              v-model="form.Modelo"
              type="text" 
              required
              class="input-field"
              placeholder="Samsung Galaxy S21"
            />
          </div>
          
          <div>
            <label class="label">
              <i class="bi bi-upc-scan"></i>
              IMEI *
            </label>
            <input 
              v-model="form.IMEI"
              type="text" 
              required
              minlength="10"
              maxlength="20"
              class="input-field"
              placeholder="123456789012345 (mínimo 10 caracteres)"
            />
            <p class="text-xs text-gray-400 mt-1">El IMEI debe tener entre 10 y 20 caracteres</p>
          </div>
          
          <div>
            <label class="label">
              <i class="bi bi-tag-fill"></i>
              Marca
            </label>
            <input 
              v-model="form.Marca"
              type="text" 
              class="input-field"
              placeholder="Samsung"
            />
          </div>
          
          <div>
            <label class="label">Empleado (Opcional)</label>
            <select v-model="form.EmpleadoID" class="input-field">
              <option value="">Sin asignar</option>
              <option v-for="empleado in empleados" :key="empleado.EmpleadoID" :value="empleado.EmpleadoID">
                {{ empleado.Nombre }} {{ empleado.Apellido }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="label">Estado</label>
            <select v-model="form.Estado" class="input-field">
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
              <option value="Mantenimiento">Mantenimiento</option>
            </select>
          </div>
          
          <div class="flex space-x-3 pt-4">
            <button 
              type="submit"
              :disabled="loading"
              class="btn-primary flex-1"
            >
              <span v-if="!loading">
                <i class="bi bi-check-circle-fill"></i>
                {{ editingDevice ? 'Actualizar' : 'Crear' }}
              </span>
              <span v-else>
                Guardando...
              </span>
            </button>
            <button 
              type="button"
              @click="closeModal"
              class="btn-secondary"
            >
              <i class="bi bi-x-circle-fill"></i>
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

interface Dispositivo {
  DispositivoID: number
  IMEI: string
  Modelo: string
  Marca?: string
  EmpleadoID?: number
  Estado: string
  FechaAsignacion?: string
  created_at?: string
  updated_at?: string
  empleado?: {
    EmpleadoID: number
    Nombre: string
    Apellido: string
  }
}

interface Empleado {
  EmpleadoID: number
  Nombre: string
  Apellido: string
}

const router = useRouter()
const dispositivos = ref<Dispositivo[]>([])
const empleados = ref<Empleado[]>([])
const searchQuery = ref('')
const filterStatus = ref('')
const showCreateModal = ref(false)
const editingDevice = ref<Dispositivo | null>(null)
const loading = ref(false)

const form = ref({
  IMEI: '',
  Modelo: '',
  Marca: '',
  EmpleadoID: '',
  Estado: 'Activo'
})

const filteredDispositivos = computed(() => {
  return dispositivos.value.filter(dispositivo => {
    const query = searchQuery.value.toLowerCase()
    const matchesSearch = (dispositivo.Modelo?.toLowerCase() || '').includes(query) ||
                         (dispositivo.IMEI || '').includes(searchQuery.value) ||
                         (dispositivo.Marca?.toLowerCase() || '').includes(query)
    const matchesStatus = !filterStatus.value || dispositivo.Estado === filterStatus.value
    return matchesSearch && matchesStatus
  })
})

onMounted(async () => {
  await loadDispositivos()
  await loadEmpleados()
})

const loadDispositivos = async () => {
  try {
    const response = await api.get('/dispositivos')
    dispositivos.value = response.data
  } catch (error) {
    console.error('Error loading devices:', error)
  }
}

const loadEmpleados = async () => {
  try {
    const response = await api.get('/empleados')
    empleados.value = response.data
  } catch (error) {
    console.error('Error loading employees:', error)
  }
}

const getDeviceIconClass = (estado: string) => {
  const base = 'w-10 h-10 rounded-lg flex items-center justify-center'
  switch (estado) {
    case 'Activo': return `${base} bg-green-500/20 text-green-500`
    case 'Inactivo': return `${base} bg-red-500/20 text-red-500`
    case 'Mantenimiento': return `${base} bg-primary/20 text-primary`
    default: return `${base} bg-primary/10 text-gray-400`
  }
}

const getStatusBadgeClass = (estado: string) => {
  switch (estado) {
    case 'Activo': return 'active'
    case 'Inactivo': return 'inactive'
    case 'Mantenimiento': return 'warning'
    default: return 'inactive'
  }
}

const formatDate = (date: string | undefined) => {
  if (!date) return 'Sin fecha'
  try {
    const parsedDate = new Date(date)
    if (isNaN(parsedDate.getTime())) return 'Fecha inválida'
    return parsedDate.toLocaleDateString('es-ES', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric'
    })
  } catch {
    return 'Fecha inválida'
  }
}

const getEmpleadoNombre = (empleadoId: number | undefined) => {
  if (!empleadoId) return 'Sin asignar'
  const empleado = empleados.value.find(e => e.EmpleadoID === empleadoId)
  return empleado ? `${empleado.Nombre} ${empleado.Apellido}` : `Empleado #${empleadoId}`
}

const editDevice = (dispositivo: Dispositivo) => {
  editingDevice.value = dispositivo
  form.value = {
    IMEI: dispositivo.IMEI,
    Modelo: dispositivo.Modelo,
    Marca: dispositivo.Marca || '',
    EmpleadoID: dispositivo.EmpleadoID?.toString() || '',
    Estado: dispositivo.Estado
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingDevice.value = null
  form.value = {
    IMEI: '',
    Modelo: '',
    Marca: '',
    EmpleadoID: '',
    Estado: 'Activo'
  }
}

const handleSubmit = async () => {
  loading.value = true
  
  try {
    const data = {
      ...form.value,
      EmpleadoID: form.value.EmpleadoID || null
    }
    
    if (editingDevice.value) {
      await api.put(`/dispositivos/${editingDevice.value.DispositivoID}`, data)
    } else {
      await api.post('/dispositivos', data)
    }
    
    await loadDispositivos()
    closeModal()
  } catch (error: any) {
    console.error('Error saving device:', error)
    alert(error.response?.data?.message || 'Error al guardar el dispositivo')
  } finally {
    loading.value = false
  }
}

const viewLocation = (dispositivo: Dispositivo) => {
  router.push('/')
}

const deleteDevice = async (id: number) => {
  if (!confirm('¿Estás seguro de eliminar este dispositivo?')) return
  
  try {
    await api.delete(`/dispositivos/${id}`)
    await loadDispositivos()
  } catch (error) {
    console.error('Error deleting device:', error)
    alert('Error al eliminar el dispositivo')
  }
}
</script>

<style scoped>
/* ============================================
   DISPOSITIVOS VIEW - CONSISTENT STYLES
   ============================================ */

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.90);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  overflow-y: auto;
}

.modal-container {
  width: 100%;
  max-width: 500px;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100%;
  padding: 20px 0;
}

.modal-content {
  background: #000000;
  border: 2px solid rgba(255, 107, 53, 0.3);
  border-radius: 16px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  animation: modal-enter 0.3s ease-out;
  box-shadow: 0 20px 60px rgba(255, 107, 53, 0.2), 0 0 100px rgba(0, 0, 0, 0.5);
}

/* Compact Elements */
.stat-mini {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  transition: all 0.2s ease;
}

.stat-mini:hover {
  background: rgba(255, 255, 255, 0.04);
  border-color: rgba(255, 107, 53, 0.2);
}

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

.input-compact:hover {
  background: rgba(255, 255, 255, 0.07);
  border-color: rgba(255, 107, 53, 0.3);
}

.input-compact::placeholder {
  color: #9ca3af;
  font-size: 13px;
}

/* Select dropdown options styling */
.input-compact option {
  background: #1f2937;
  color: #ffffff;
  padding: 12px;
  font-size: 14px;
  font-weight: 500;
}

.input-compact option:hover {
  background: #374151;
}

.input-compact option:checked {
  background: linear-gradient(135deg, #FF6B35 0%, #FF8C5E 100%);
  color: #ffffff;
  font-weight: 600;
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

.btn-primary-compact:active {
  transform: translateY(0);
}

/* Action Button - Location variant (specific to Dispositivos) */
.action-btn.location {
  color: #FF6B35;
  border-color: rgba(255, 107, 53, 0.3);
}

.action-btn.location:hover {
  background: rgba(255, 107, 53, 0.15);
  border-color: rgba(255, 107, 53, 0.5);
  box-shadow: 0 8px 16px rgba(255, 107, 53, 0.3);
}

/* Status Badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid;
  transition: all 0.3s ease;
}

.status-badge.active {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.3);
  color: #10b981;
}

.status-badge.inactive {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.3);
  color: #ef4444;
}

.status-badge.warning {
  background: rgba(255, 107, 53, 0.1);
  border-color: rgba(255, 107, 53, 0.3);
  color: #FF6B35;
}

.status-badge:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  animation: pulse-dot 2s ease-in-out infinite;
}

.status-badge.active .status-dot {
  background: #10b981;
}

.status-badge.inactive .status-dot {
  background: #ef4444;
}

.status-badge.warning .status-dot {
  background: #FF6B35;
}

@keyframes pulse-dot {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.2);
  }
}

/* Legacy Styles for Modal */
.label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #e5e7eb;
  margin-bottom: 8px;
}

.label i {
  color: #FF6B35;
  font-size: 16px;
}

.input-field {
  width: 100%;
  padding: 12px 16px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  color: white;
  transition: all 0.3s ease;
  font-size: 14px;
}

.input-field:focus {
  outline: none;
  border-color: #FF6B35;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.input-field:hover {
  border-color: rgba(255, 107, 53, 0.3);
  background: rgba(255, 255, 255, 0.07);
}

.input-field::placeholder {
  color: #6b7280;
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

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(255, 107, 53, 0.4);
}

.btn-primary:active {
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
}

.btn-secondary:hover {
  border-color: rgba(255, 107, 53, 0.5);
  background: rgba(255, 107, 53, 0.1);
  transform: translateY(-1px);
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

@keyframes modal-enter {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-20px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.animate-modal-enter {
  animation: modal-enter 0.3s ease-out;
}
</style>
