import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

interface User {
  UsuarioID: number
  Nombre: string
  Email: string
  Rol: string
  Estado: string
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  
  // Inicializar usuario desde localStorage si existe token
  const storedUser = localStorage.getItem('user')
  if (token.value && storedUser) {
    try {
      user.value = JSON.parse(storedUser)
    } catch (e) {
      console.error('Error parsing stored user:', e)
    }
  }
  
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isAdmin = computed(() => user.value?.Rol === 'Administrador')
  
  async function login(email: string, password: string) {
    try {
      const response = await api.post('/auth/login', {
        Email: email,
        Contraseña: password
      })
      
      token.value = response.data.token
      user.value = response.data.usuario
      localStorage.setItem('token', response.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.usuario))
      
      return { success: true }
    } catch (error: any) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Error al iniciar sesión' 
      }
    }
  }
  
  async function logout() {
    try {
      await api.post('/auth/logout')
    } catch (error) {
      console.error('Error al cerrar sesión:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }
  
  async function fetchUser() {
    try {
      const response = await api.get('/auth/me')
      user.value = response.data.usuario
      localStorage.setItem('user', JSON.stringify(response.data.usuario))
    } catch (error) {
      console.error('Error al obtener usuario:', error)
      logout()
    }
  }
  
  // Inicializar usuario si hay token pero no usuario
  async function initialize() {
    if (token.value && !user.value) {
      await fetchUser()
    }
  }
  
  return {
    user,
    token,
    isAuthenticated,
    isAdmin,
    login,
    logout,
    fetchUser,
    initialize
  }
})
