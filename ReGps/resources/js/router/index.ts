import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'Login',
      component: () => import('@/views/Login.vue'),
      meta: { requiresAuth: false }
    },
    // Ruta para Empleados
    {
      path: '/empleado',
      name: 'EmpleadoDashboard',
      component: () => import('@/views/EmpleadoDashboard.vue'),
      meta: { requiresAuth: true, requiresEmployee: true }
    },
    // Rutas para Administradores
    {
      path: '/',
      name: 'Dashboard',
      component: () => import('@/views/Dashboard.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/historial',
      name: 'Historial',
      component: () => import('@/views/Historial.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/zonas',
      name: 'Zonas',
      component: () => import('@/views/Zonas.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/zonas/crear',
      name: 'CrearZona',
      component: () => import('@/views/CrearZona.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/zonas/editar/:id',
      name: 'EditarZona',
      component: () => import('@/views/CrearZona.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/alertas',
      name: 'Alertas',
      component: () => import('@/views/Alertas.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/usuarios',
      name: 'Usuarios',
      component: () => import('@/views/Usuarios.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
    {
      path: '/dispositivos',
      name: 'Dispositivos',
      component: () => import('@/views/Dispositivos.vue'),
      meta: { requiresAuth: true, requiresAdmin: true }
    },
  ]
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Inicializar usuario si hay token
  await authStore.initialize()
  
  // Si no está autenticado y requiere auth, redirigir a login
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/login')
    return
  }
  
  // Si está en login y ya está autenticado, redirigir según rol
  if (to.path === '/login' && authStore.isAuthenticated) {
    if (authStore.isAdmin) {
      next('/')
    } else {
      next('/empleado')
    }
    return
  }
  
  // Verificar acceso de administrador
  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next('/empleado') // Empleados redirigidos a su dashboard
    return
  }
  
  // Verificar acceso de empleado
  if (to.meta.requiresEmployee && authStore.isAdmin) {
    next('/') // Admins redirigidos al dashboard principal
    return
  }
  
  next()
})

export default router
