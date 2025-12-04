<template>
  <div id="app">
    <Navbar v-if="showNavbar" />
    <main class="main-content">
      <router-view />
    </main>
    <Footer v-if="showFooter" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/NavbarNew.vue'
import Footer from '@/components/Footer.vue'

const route = useRoute()

// Ocultar Navbar y Footer en la página de login
const showNavbar = computed(() => route.path !== '/login')
const showFooter = computed(() => route.path !== '/login')
</script>

<style>
.main-content {
  flex: 1;
  min-height: calc(100vh - 4rem - 300px); /* altura viewport - navbar - footer aprox */
}
</style>

