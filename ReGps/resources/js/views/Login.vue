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
.bg-gradient-to-br {
  background: linear-gradient(135deg, var(--color-dark) 0%, var(--color-dark-100) 50%, var(--color-dark) 100%);
}
</style>
