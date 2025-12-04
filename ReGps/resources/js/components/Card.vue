<template>
  <div class="card" :class="cardClasses">
    <!-- Header -->
    <div v-if="$slots.header || title" class="card-header">
      <div v-if="title" class="card-title">
        <i v-if="icon" :class="icon" class="card-icon"></i>
        {{ title }}
      </div>
      <slot name="header"></slot>
    </div>

    <!-- Body -->
    <div class="card-body">
      <slot></slot>
    </div>

    <!-- Footer -->
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  title?: string
  icon?: string
  hover?: boolean
  padding?: 'none' | 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  hover: true,
  padding: 'md'
})

const cardClasses = computed(() => [
  {
    'card-hover': props.hover,
    [`card-padding-${props.padding}`]: true
  }
])
</script>

<style scoped>
.card {
  background-color: var(--color-dark-100);
  border: 1px solid var(--color-dark-300);
  border-radius: var(--radius-lg);
  transition: all var(--transition-fast);
}

.card-hover:hover {
  border-color: var(--color-primary);
  box-shadow: 0 0 20px rgba(255, 107, 53, 0.15);
  transform: translateY(-2px);
}

.card-header {
  border-bottom: 1px solid var(--color-dark-300);
  padding: 1rem 1.5rem;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.card-icon {
  color: var(--color-primary);
  font-size: 1.5rem;
}

.card-body {
  padding: 1.5rem;
}

.card-footer {
  border-top: 1px solid var(--color-dark-300);
  padding: 1rem 1.5rem;
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

/* Padding variants */
.card-padding-none .card-body {
  padding: 0;
}

.card-padding-sm .card-body {
  padding: 0.75rem;
}

.card-padding-md .card-body {
  padding: 1.5rem;
}

.card-padding-lg .card-body {
  padding: 2rem;
}

/* Mobile */
@media (max-width: 640px) {
  .card-body {
    padding: 1rem;
  }

  .card-header,
  .card-footer {
    padding: 0.75rem 1rem;
  }

  .card-footer {
    flex-direction: column;
  }
}
</style>
