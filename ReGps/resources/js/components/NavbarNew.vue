<template>
  <nav class="navbar">
    <div class="navbar-container">
      <!-- Logo -->
      <router-link to="/" class="navbar-logo">
        <div class="logo-icon">
          <i class="bi bi-broadcast"></i>
        </div>
        <span class="logo-text">Re<span class="logo-accent">GPS</span></span>
      </router-link>

      <!-- Desktop Navigation -->
      <div class="desktop-nav">
        <template v-if="authStore.isAdmin">
          <router-link to="/" class="nav-item">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
          </router-link>
          <router-link to="/historial" class="nav-item">
            <i class="bi bi-clock-history"></i>
            <span>Historial</span>
          </router-link>
          <router-link to="/zonas" class="nav-item">
            <i class="bi bi-geo-alt-fill"></i>
            <span>Zonas</span>
          </router-link>
          <router-link to="/alertas" class="nav-item">
            <i class="bi bi-bell-fill"></i>
            <span>Alertas</span>
          </router-link>
          <router-link to="/usuarios" class="nav-item">
            <i class="bi bi-person-fill"></i>
            <span>Usuarios</span>
          </router-link>
          <router-link to="/dispositivos" class="nav-item">
            <i class="bi bi-phone-fill"></i>
            <span>Dispositivos</span>
          </router-link>
        </template>
        <template v-else>
          <router-link to="/empleado" class="nav-item">
            <i class="bi bi-speedometer2"></i>
            <span>Mi Panel</span>
          </router-link>
        </template>
      </div>

      <!-- Desktop User Info -->
      <div class="desktop-user">
        <div class="user-details">
          <p class="user-name">{{ authStore.user?.Nombre }}</p>
          <p class="user-role">{{ authStore.user?.Rol }}</p>
        </div>
        <button @click="logout" class="btn-logout">
          <i class="bi bi-box-arrow-right"></i>
          <span>Salir</span>
        </button>
      </div>

      <!-- Mobile Hamburger -->
      <button @click="toggleMenu" class="btn-hamburger">
        <span :class="{ active: menuOpen }"></span>
        <span :class="{ active: menuOpen }"></span>
        <span :class="{ active: menuOpen }"></span>
      </button>
    </div>

    <!-- Mobile Menu Overlay -->
    <transition name="fade">
      <div v-if="menuOpen" class="mobile-overlay" @click="closeMenu"></div>
    </transition>

    <!-- Mobile Sidebar Menu -->
    <transition name="slide-left">
      <div v-if="menuOpen" class="mobile-sidebar">
        <!-- User Info -->
        <div class="mobile-header">
          <p class="mobile-name">{{ authStore.user?.Nombre }}</p>
          <p class="mobile-role">{{ authStore.user?.Rol }}</p>
        </div>

        <!-- Navigation Links -->
        <nav class="mobile-nav">
          <template v-if="authStore.isAdmin">
            <router-link to="/" @click="closeMenu" class="mobile-item">
              <i class="bi bi-speedometer2"></i>
              <span>Dashboard</span>
            </router-link>
            <router-link to="/historial" @click="closeMenu" class="mobile-item">
              <i class="bi bi-clock-history"></i>
              <span>Historial</span>
            </router-link>
            <router-link to="/zonas" @click="closeMenu" class="mobile-item">
              <i class="bi bi-geo-alt-fill"></i>
              <span>Zonas</span>
            </router-link>
            <router-link to="/alertas" @click="closeMenu" class="mobile-item">
              <i class="bi bi-bell-fill"></i>
              <span>Alertas</span>
            </router-link>
            <router-link to="/usuarios" @click="closeMenu" class="mobile-item">
              <i class="bi bi-person-fill"></i>
              <span>Usuarios</span>
            </router-link>
            <router-link to="/dispositivos" @click="closeMenu" class="mobile-item">
              <i class="bi bi-phone-fill"></i>
              <span>Dispositivos</span>
            </router-link>
          </template>
          <template v-else>
            <router-link to="/empleado" @click="closeMenu" class="mobile-item">
              <i class="bi bi-speedometer2"></i>
              <span>Mi Panel</span>
            </router-link>
          </template>
        </nav>

        <!-- Logout Button -->
        <button @click="logout" class="mobile-btn-logout">
          <i class="bi bi-box-arrow-right"></i>
          <span>Cerrar Sesión</span>
        </button>
      </div>
    </transition>
  </nav>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const menuOpen = ref(false)

// Toggle menu
const toggleMenu = () => {
  menuOpen.value = !menuOpen.value
  document.body.style.overflow = menuOpen.value ? 'hidden' : ''
}

// Close menu
const closeMenu = () => {
  menuOpen.value = false
  document.body.style.overflow = ''
}

// Logout
const logout = async () => {
  closeMenu()
  await authStore.logout()
  router.push('/login')
}

// Close menu on route change
watch(() => route.path, () => {
  closeMenu()
})
</script>

<style scoped>
/* === NAVBAR === */
.navbar {
  position: sticky;
  top: 0;
  z-index: 50000;
  background: linear-gradient(135deg, #1a1d23 0%, #0f1115 100%);
  border-bottom: 1px solid rgba(255, 107, 53, 0.2);
  backdrop-filter: blur(10px);
}

.navbar-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1rem;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* === LOGO === */
.navbar-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
}

.logo-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #FF6B35 0%, #e85a2a 100%);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: white;
  box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
}

.logo-text {
  font-size: 24px;
  font-weight: 700;
  color: white;
}

.logo-accent {
  color: #FF6B35;
}

/* === DESKTOP NAV === */
.desktop-nav {
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: 8px;
  color: #9ca3af;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
}

.nav-item:hover {
  color: white;
  background: rgba(255, 107, 53, 0.1);
}

.nav-item.router-link-active {
  color: #FF6B35;
  background: rgba(255, 107, 53, 0.1);
}

.nav-item i {
  font-size: 18px;
}

/* === DESKTOP USER === */
.desktop-user {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-details {
  text-align: right;
}

.user-name {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: white;
}

.user-role {
  margin: 0;
  font-size: 12px;
  color: #FF6B35;
}

.btn-logout {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  color: #ef4444;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-logout:hover {
  background: rgba(239, 68, 68, 0.2);
}

/* === HAMBURGER === */
.btn-hamburger {
  display: none;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 40px;
  height: 40px;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0;
  gap: 6px;
}

.btn-hamburger span {
  width: 24px;
  height: 2px;
  background: white;
  border-radius: 2px;
  transition: all 0.3s;
}

.btn-hamburger span.active:nth-child(1) {
  transform: rotate(45deg) translateY(8px);
}

.btn-hamburger span.active:nth-child(2) {
  opacity: 0;
}

.btn-hamburger span.active:nth-child(3) {
  transform: rotate(-45deg) translateY(-8px);
}

/* === MOBILE OVERLAY === */
.mobile-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  z-index: 49998;
  backdrop-filter: blur(4px);
}

/* === MOBILE SIDEBAR === */
.mobile-sidebar {
  position: fixed;
  top: 0;
  right: 0;
  width: 280px;
  max-width: 80vw;
  height: 100vh;
  height: 100dvh; /* Dynamic viewport height for mobile */
  background: #000000;
  z-index: 49999;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 24px 20px 24px 20px;
  display: flex;
  flex-direction: column;
  box-shadow: -4px 0 20px rgba(0, 0, 0, 0.5);
  -webkit-overflow-scrolling: touch;
}

.mobile-header {
  padding: 16px;
  background: #1a1a1a;
  border: 1px solid #FF6B35;
  border-radius: 10px;
  margin-bottom: 20px;
  flex-shrink: 0; /* Prevent header from shrinking */
}

.mobile-name {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: bold;
  color: white;
}

.mobile-role {
  margin: 0;
  font-size: 14px;
  color: #FF6B35;
}

.mobile-nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px; /* Space before logout button */
}

.mobile-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #1a1a1a;
  border: 1px solid #333;
  border-radius: 8px;
  color: white;
  text-decoration: none;
  font-size: 16px;
  font-weight: 500;
  transition: all 0.2s;
}

.mobile-item:active,
.mobile-item.router-link-active {
  background: #FF6B35;
  border-color: #FF6B35;
  color: #000000;
}

.mobile-item i {
  font-size: 20px;
  width: 24px;
  text-align: center;
}

.mobile-btn-logout {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px;
  margin-top: auto;
  margin-bottom: 0;
  background: #dc2626;
  border: none;
  border-radius: 8px;
  color: white;
  font-size: 16px;
  font-weight: bold;
  cursor: pointer;
  transition: background 0.2s;
  flex-shrink: 0; /* Prevent button from shrinking */
}

.mobile-btn-logout:active {
  background: #b91c1c;
}

/* === TRANSITIONS === */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
  transform: translateX(100%);
}

/* === RESPONSIVE === */

/* Mobile Small (hasta 480px) */
@media (max-width: 480px) {
  .desktop-nav,
  .desktop-user {
    display: none;
  }

  .btn-hamburger {
    display: flex;
  }

  .mobile-sidebar {
    width: 90vw;
    max-width: 320px;
    padding: 20px 16px;
  }

  .mobile-header {
    padding: 14px;
    margin-bottom: 16px;
  }

  .mobile-name {
    font-size: 15px;
  }

  .mobile-role {
    font-size: 13px;
  }

  .mobile-item {
    padding: 14px 12px;
    font-size: 15px;
    gap: 12px;
  }

  .mobile-item i {
    font-size: 18px;
  }

  .mobile-btn-logout {
    padding: 14px;
    font-size: 15px;
  }

  .navbar-container {
    padding: 0 0.75rem;
  }

  .logo-text {
    font-size: 18px;
  }

  .logo-icon {
    width: 36px;
    height: 36px;
    font-size: 18px;
  }
}

/* Mobile Medium (481px - 640px) */
@media (min-width: 481px) and (max-width: 640px) {
  .desktop-nav,
  .desktop-user {
    display: none;
  }

  .btn-hamburger {
    display: flex;
  }

  .mobile-sidebar {
    width: 300px;
    max-width: 85vw;
  }
}

/* Tablet (641px - 768px) */
@media (min-width: 641px) and (max-width: 768px) {
  .desktop-nav,
  .desktop-user {
    display: none;
  }

  .btn-hamburger {
    display: flex;
  }

  .mobile-sidebar {
    width: 320px;
    max-width: 400px;
  }

  .mobile-item {
    padding: 16px 18px;
    gap: 18px;
  }

  .mobile-item i {
    font-size: 22px;
  }
}

/* Desktop (769px+) */
@media (min-width: 769px) {
  .btn-hamburger,
  .mobile-overlay,
  .mobile-sidebar {
    display: none !important;
  }

  .desktop-nav {
    display: flex;
  }

  .desktop-user {
    display: flex;
  }
}

/* Large Desktop (1024px+) - Opcional: mejoras para pantallas grandes */
@media (min-width: 1024px) {
  .navbar-container {
    max-width: 1400px;
  }

  .nav-item {
    padding: 12px 20px;
  }
}
</style>
