<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-container" :class="sizeClass">
      <!-- Header -->
      <div class="modal-header">
        <h3 class="modal-title">
          <i v-if="icon" :class="icon" class="modal-icon"></i>
          {{ title }}
        </h3>
        <button 
          v-if="showClose"
          @click="$emit('close')" 
          class="modal-close"
          aria-label="Cerrar modal"
        >
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <slot></slot>
      </div>

      <!-- Footer -->
      <div v-if="$slots.footer" class="modal-footer">
        <slot name="footer"></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'

interface Props {
  title: string
  size?: 'sm' | 'md' | 'lg' | 'xl'
  icon?: string
  showClose?: boolean
  closeOnOverlay?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  showClose: true,
  closeOnOverlay: true
})

const emit = defineEmits<{
  close: []
}>()

const sizeClass = computed(() => `modal-${props.size}`)

const handleOverlayClick = (e: MouseEvent) => {
  if (props.closeOnOverlay && e.target === e.currentTarget) {
    emit('close')
  }
}

const handleEscape = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    emit('close')
  }
}

onMounted(() => {
  document.body.style.overflow = 'hidden'
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.body.style.overflow = ''
  document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-container {
  background: linear-gradient(180deg, var(--color-dark-100) 0%, var(--color-dark) 100%);
  border: 1px solid var(--color-dark-300);
  border-radius: var(--radius-xl);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

/* Tamaños */
.modal-sm { width: 100%; max-width: 24rem; }
.modal-md { width: 100%; max-width: 32rem; }
.modal-lg { width: 100%; max-width: 48rem; }
.modal-xl { width: 100%; max-width: 64rem; }

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem;
  border-bottom: 1px solid var(--color-dark-300);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.modal-icon {
  color: var(--color-primary);
  font-size: 1.5rem;
}

.modal-close {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background-color: transparent;
  color: var(--color-gray-400);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.modal-close:hover {
  background-color: var(--color-dark-200);
  color: white;
}

.modal-body {
  flex: 1;
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-footer {
  padding: 1.5rem;
  border-top: 1px solid var(--color-dark-300);
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

/* Mobile */
@media (max-width: 640px) {
  .modal-container {
    width: 100%;
    max-width: none;
    max-height: 100vh;
    border-radius: 0;
  }

  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }

  .modal-footer {
    flex-direction: column-reverse;
  }
}
</style>
