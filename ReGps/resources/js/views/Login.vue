<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-dark via-dark-100 to-dark px-4">
    <div class="max-w-md w-full">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-primary rounded-2xl mb-4">
          <i class="bi bi-broadcast text-4xl text-white"></i>
        </div>
        <h1 class="text-4xl font-bold text-white mb-2">
          Re<span class="text-primary">GPS</span>
        </h1>
        <p class="text-gray-400">Sistema de Rastreo GPS en Tiempo Real</p>
      </div>
      
      <!-- Login Form -->
      <div class="bg-dark-100 rounded-2xl shadow-2xl p-8 border border-primary/20">
        <h2 class="text-2xl font-bold text-white mb-6">Iniciar Sesión</h2>
        
        <form @submit.prevent="handleLogin" class="space-y-6">
          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">
              Correo Electrónico
            </label>
            <div class="relative">
              <input
                v-model="email"
                type="email"
                required
                class="input-field"
                placeholder="usuario@regps.com"
              />
            </div>
          </div>
          
          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-300 mb-2">
              Contraseña
            </label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="input-field pr-10"
                placeholder="••••••••"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-primary transition-colors"
              >
                <i :class="showPassword ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill'"></i>
              </button>
            </div>
          </div>
          
          <!-- Error Message -->
          <div v-if="errorMessage" class="bg-red-500/10 border border-red-500/50 rounded-lg p-3">
            <p class="text-red-500 text-sm flex items-center">
              {{ errorMessage }}
            </p>
          </div>
          
          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading"
            class="btn-primary w-full"
          >
            <span v-if="!loading" class="flex items-center justify-center gap-2">
              <i class="bi bi-box-arrow-in-right"></i>
              Iniciar Sesión
            </span>
            <span v-else class="flex items-center justify-center">
              Iniciando...
            </span>
          </button>
        </form>
        
        <!-- Test Credentials -->
        <div class="mt-6 pt-6 border-t border-primary/20">
          <p class="text-xs text-gray-400 text-center mb-3">Credenciales de prueba:</p>
          <div class="grid grid-cols-2 gap-3">
            <button
              @click="fillTestCredentials('admin')"
              class="px-3 py-2 bg-primary/10 hover:bg-primary/20 text-primary text-xs rounded-lg transition-colors"
            >
              Admin
            </button>
            <button
              @click="fillTestCredentials('empleado')"
              class="px-3 py-2 bg-primary/10 hover:bg-primary/20 text-primary text-xs rounded-lg transition-colors"
            >
              Empleado
            </button>
          </div>
        </div>
      </div>
      
      <!-- Footer -->
      <p class="text-center text-gray-500 text-sm mt-8">
        © 2025 ReGPS. Sistema de Rastreo GPS.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
  loading.value = true
  errorMessage.value = ''
  
  const result = await authStore.login(email.value, password.value)
  
  if (result.success) {
    await authStore.fetchUser()
    router.push('/')
  } else {
    errorMessage.value = result.message || 'Error al iniciar sesión'
  }
  
  loading.value = false
}

const fillTestCredentials = (type: 'admin' | 'empleado') => {
  if (type === 'admin') {
    email.value = 'test@regps.com'
    password.value = '123456'
  } else {
    email.value = 'empleado@regps.com'
    password.value = '123456'
  }
}
</script>

<style scoped>
/* Mobile First - Base styles */
.min-h-screen {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bg-gradient-to-br {
  background: linear-gradient(135deg, var(--color-dark) 0%, var(--color-dark-100) 50%, var(--color-dark) 100%);
}

/* Contenedor responsive */
.max-w-md {
  max-width: 28rem;
  width: 100%;
}

/* Card responsive */
.bg-dark-100 {
  background-color: var(--color-dark-100);
}

.rounded-2xl {
  border-radius: 1rem;
}

.shadow-2xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
}

.border-primary\/20 {
  border: 1px solid rgba(255, 107, 53, 0.2);
}

/* Input field mejorado */
.input-field {
  width: 100%;
  padding: 0.875rem 1rem;
  background-color: var(--color-dark-200);
  border: 1px solid var(--color-dark-300);
  border-radius: var(--radius-md);
  color: white;
  font-size: 0.875rem;
  transition: all var(--transition-fast);
}

.input-field:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
}

.input-field::placeholder {
  color: var(--color-gray-500);
}

/* Button mejorado */
.btn-primary {
  width: 100%;
  padding: 0.875rem 1.5rem;
  background: linear-gradient(135deg, var(--color-primary) 0%, var(--color-primary-dark) 100%);
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  border: none;
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Mobile (< 640px) */
@media (max-width: 640px) {
  .max-w-md {
    padding: 0 1rem;
  }

  .bg-dark-100 {
    padding: 1.5rem;
  }

  .text-4xl {
    font-size: 2rem;
  }

  .text-2xl {
    font-size: 1.5rem;
  }

  .w-20 {
    width: 4rem;
    height: 4rem;
  }

  .grid-cols-2 {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
}

/* Tablet (641px - 1024px) */
@media (min-width: 641px) and (max-width: 1024px) {
  .max-w-md {
    max-width: 32rem;
  }
}

/* Desktop (> 1024px) */
@media (min-width: 1025px) {
  .bg-dark-100:hover {
    border-color: var(--color-primary);
    box-shadow: 0 0 30px rgba(255, 107, 53, 0.2);
  }
}
</style>
