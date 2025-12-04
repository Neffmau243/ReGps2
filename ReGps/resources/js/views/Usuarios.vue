<template>
  <div class="usuarios-view min-h-screen">
    <div class="container py-6 pb-40 max-w-7xl mx-auto px-4">
      <!-- Compact Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div>
              <h1 class="text-2xl font-bold text-white">Usuarios</h1>
              <p class="text-gray-500 text-xs">{{ usuarios.length }} registros</p>
            </div>
          </div>
          <button 
            @click="showCreateModal = true"
            class="btn-primary-compact"
          >
            <i class="bi bi-person-plus-fill"></i> Nuevo
          </button>
        </div>

        <!-- Inline Stats + Filters -->
        <div class="flex items-center gap-8 flex-wrap">
          <!-- Mini Stats -->
          <div class="flex items-center gap-6">
            <div class="stat-mini">
              <span class="text-white font-semibold text-sm">{{ usuarios.filter(u => u.Estado === 'Activo').length }}</span>
              <span class="text-gray-500 text-xs">Activos</span>
            </div>
            <div class="stat-mini">
              <span class="text-white font-semibold text-sm">{{ usuarios.filter(u => u.Rol === 'Administrador').length }}</span>
              <span class="text-gray-500 text-xs">Admins</span>
            </div>
          </div>

          <!-- Divider -->
          <div class="h-8 w-px bg-primary/20"></div>

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
            <select v-model="filterRole" class="input-compact">
              <option value="">Todos</option>
              <option value="Administrador">Admin</option>
              <option value="Empleado">Empleado</option>
            </select>
            <select v-model="filterStatus" class="input-compact">
              <option value="">Estado</option>
              <option value="Activo">Activo</option>
              <option value="Inactivo">Inactivo</option>
            </select>
          </div>
        </div>
      </div>
      
      <!-- Users List -->
      <div class="space-y-6">
        <div 
          v-for="usuario in filteredUsuarios" 
          :key="usuario.UsuarioID"
          class="usuario-card"
        >
          <div class="flex items-start justify-between">
            <div class="flex items-start flex-1">
              <!-- Content -->
              <div class="flex-1">
                <div class="flex items-center gap-4 mb-4">
                  <h3 class="text-lg font-bold text-white">{{ usuario.Nombre }}</h3>
                  <span :class="getRoleBadge(usuario.Rol)">
                    {{ usuario.Rol }}
                  </span>
                  <span :class="getStatusBadge(usuario.Estado)">
                    {{ usuario.Estado }}
                  </span>
                </div>
                
                <p class="text-gray-300 mb-4 flex items-center gap-3">
                  <i class="bi bi-envelope-fill text-gray-400"></i>
                  {{ usuario.Email }}
                </p>
                
                <div class="flex items-center gap-8 text-sm text-gray-400">
                  <div class="flex items-center gap-2.5">
                    <i class="bi bi-hash"></i>
                    <span>ID: #{{ usuario.UsuarioID }}</span>
                  </div>
                  <div class="flex items-center space-x-2">
                    <i class="bi bi-clock-fill"></i>
                    <span>{{ usuario.UltimoLogin ? formatDate(usuario.UltimoLogin) : 'Sin actividad reciente' }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-3">
              <button 
                @click="editUser(usuario)"
                class="action-btn edit"
                title="Editar"
              >
                <i class="bi bi-pencil-fill"></i>
              </button>
              <button 
                @click="toggleStatus(usuario)"
                class="action-btn toggle"
                :title="usuario.Estado === 'Activo' ? 'Desactivar' : 'Activar'"
              >
                <i :class="usuario.Estado === 'Activo' ? 'bi bi-toggle-on' : 'bi bi-toggle-off'"></i>
              </button>
              <button 
                @click="deleteUser(usuario.UsuarioID)"
                class="action-btn delete"
                title="Eliminar"
              >
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="filteredUsuarios.length === 0" class="bg-dark-100 rounded-xl border border-primary/20 p-12 text-center">
        <h3 class="text-xl font-bold text-white mb-2">No hay usuarios</h3>
        <p class="text-gray-400">No se encontraron usuarios con los filtros seleccionados</p>
      </div>
    </div>
    
    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || editingUser" class="modal-overlay">
      <div class="modal-container" @click.self="closeModal">
        <div class="modal-content">
          <!-- Modal Header -->
          <div class="p-6 border-b border-primary/20 flex items-center justify-between">
            <h3 class="text-xl font-bold text-white flex items-center gap-2">
              <i class="bi" :class="editingUser ? 'bi-pencil-square' : 'bi-person-plus-fill'"></i>
              {{ editingUser ? 'Editar Usuario' : 'Nuevo Usuario' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-white transition-colors">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
          
          <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
            <div>
              <label class="label">
                <i class="bi bi-person-fill"></i>
                Nombre Completo *
              </label>
              <input 
                v-model="form.Nombre"
                type="text" 
                required
                class="input-field"
                placeholder="Ej: Juan Pérez García"
              />
            </div>
            
            <div>
              <label class="label">
                <i class="bi bi-envelope-fill"></i>
                Email *
              </label>
              <input 
                v-model="form.Email"
                type="email" 
                required
                class="input-field"
                placeholder="ejemplo@regps.com"
              />
            </div>
            
            <div v-if="!editingUser">
              <label class="label">
                <i class="bi bi-key-fill"></i>
                Contraseña *
              </label>
              <input 
                v-model="form.Contraseña"
                type="password" 
                required
                class="input-field"
                placeholder="Mínimo 8 caracteres"
              />
            </div>
            
            <div v-else>
              <label class="label">
                <i class="bi bi-key-fill"></i>
                Nueva Contraseña (opcional)
              </label>
              <input 
                v-model="form.Contraseña"
                type="password" 
                class="input-field"
                placeholder="Dejar en blanco para mantener la actual"
              />
              <p class="text-xs text-gray-400 mt-1">
                💡 Solo completa este campo si deseas cambiar la contraseña
              </p>
            </div>
            
            <div>
              <label class="label">
                <i class="bi bi-shield-fill"></i>
                Rol *
              </label>
              <select v-model="form.Rol" required class="input-field">
                <option value="" disabled>Seleccionar rol</option>
                <option value="Administrador">👑 Administrador</option>
                <option value="Empleado">👤 Empleado</option>
              </select>
            </div>
            
            <div>
              <label class="label">Estado</label>
              <select v-model="form.Estado" class="input-field">
                <option value="Activo">✅ Activo</option>
                <option value="Inactivo">⛔ Inactivo</option>
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
                  {{ editingUser ? 'Actualizar' : 'Crear' }}
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
import api from '@/services/api'

interface Usuario {
  UsuarioID: number
  Nombre: string
  Email: string
  Rol: string
  Estado: string
  UltimoLogin?: string
}

const usuarios = ref<Usuario[]>([])
const searchQuery = ref('')
const filterRole = ref('')
const filterStatus = ref('')
const showCreateModal = ref(false)
const editingUser = ref<Usuario | null>(null)
const loading = ref(false)

const form = ref({
  Nombre: '',
  Email: '',
  Contraseña: '',
  Rol: '',
  Estado: 'Activo'
})

const filteredUsuarios = computed(() => {
  return usuarios.value.filter(usuario => {
    const matchesSearch = usuario.Nombre.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         usuario.Email.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesRole = !filterRole.value || usuario.Rol === filterRole.value
    const matchesStatus = !filterStatus.value || usuario.Estado === filterStatus.value
    return matchesSearch && matchesRole && matchesStatus
  })
})

onMounted(async () => {
  await loadUsuarios()
})

const loadUsuarios = async () => {
  try {
    const response = await api.get('/usuarios')
    usuarios.value = response.data
  } catch (error) {
    console.error('Error loading users:', error)
  }
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

const editUser = (usuario: Usuario) => {
  editingUser.value = usuario
  form.value = {
    Nombre: usuario.Nombre,
    Email: usuario.Email,
    Contraseña: '',
    Rol: usuario.Rol,
    Estado: usuario.Estado
  }
}

const closeModal = () => {
  showCreateModal.value = false
  editingUser.value = null
  form.value = {
    Nombre: '',
    Email: '',
    Contraseña: '',
    Rol: '',
    Estado: 'Activo'
  }
}

const handleSubmit = async () => {
  loading.value = true
  
  try {
    if (editingUser.value) {
      // Preparar datos para edición
      const updateData: any = {
        Nombre: form.value.Nombre,
        Email: form.value.Email,
        Rol: form.value.Rol,
        Estado: form.value.Estado
      }
      
      // Solo incluir contraseña si se proporcionó una nueva
      if (form.value.Contraseña && form.value.Contraseña.trim() !== '') {
        updateData.Contraseña = form.value.Contraseña
      }
      
      await api.put(`/usuarios/${editingUser.value.UsuarioID}`, updateData)
    } else {
      await api.post('/usuarios', form.value)
    }
    
    await loadUsuarios()
    closeModal()
  } catch (error: any) {
    console.error('Error saving user:', error)
    alert(error.response?.data?.message || 'Error al guardar el usuario')
  } finally {
    loading.value = false
  }
}

const toggleStatus = async (usuario: Usuario) => {
  try {
    const newStatus = usuario.Estado === 'Activo' ? 'Inactivo' : 'Activo'
    await api.put(`/usuarios/${usuario.UsuarioID}`, { Estado: newStatus })
    await loadUsuarios()
  } catch (error) {
    console.error('Error toggling status:', error)
    alert('Error al cambiar el estado')
  }
}

const deleteUser = async (id: number) => {
  if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) return
  
  try {
    await api.delete(`/usuarios/${id}`)
    await loadUsuarios()
  } catch (error) {
    console.error('Error deleting user:', error)
    alert('Error al eliminar el usuario')
  }
}

const getRoleBadge = (rol: string) => {
  const base = 'px-2 py-1 text-xs rounded-full font-medium'
  return rol === 'Administrador'
    ? `${base} bg-primary/20 text-primary`
    : `${base} bg-green-500/20 text-green-500`
}

const getStatusBadge = (estado: string) => {
  return estado === 'Activo'
    ? 'px-2 py-1 bg-green-500/20 text-green-500 text-xs rounded-full'
    : 'px-2 py-1 bg-primary/10 text-gray-400 text-xs rounded-full'
}

</script>

<style scoped>
/* Usuario Card Specific Styles */
.usuario-card {
  background: transparent;
  border: 1px solid rgba(255, 107, 53, 0.2);
  border-radius: 12px;
  padding: 24px;
  transition: all 0.3s ease;
  position: relative;
}

.usuario-card::before {
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

.usuario-card:hover {
  background: rgba(255, 107, 53, 0.05);
  border-color: rgba(255, 107, 53, 0.4);
  transform: translateX(4px);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
}

.usuario-card:hover::before {
  opacity: 1;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .usuario-card {
    padding: 20px;
  }
}

@media (max-width: 992px) {
  .usuario-card {
    padding: 16px;
  }
  
  .usuario-card .flex {
    flex-direction: column;
    align-items: flex-start !important;
  }
  
  .usuario-card .action-btn {
    width: 36px;
    height: 36px;
  }
}

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
