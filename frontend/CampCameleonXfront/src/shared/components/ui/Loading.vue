<template>
  <div class="loading" :class="[sizeClass, variantClass]">
    <div class="spinner"></div>
    <p v-if="text" class="loading-text">{{ text }}</p>
  </div>
</template>

<script>
export default {
  name: 'Loading',
  props: {
    text: {
      type: String,
      default: 'Chargement...'
    },
    size: {
      type: String,
      default: 'md',
      validator: value => ['sm', 'md', 'lg'].includes(value)
    },
    variant: {
      type: String,
      default: 'default',
      validator: value => ['default', 'light', 'dark'].includes(value)
    }
  },
  computed: {
    sizeClass() {
      return `loading-${this.size}`
    },
    variantClass() {
      return `loading-${this.variant}`
    }
  }
}
</script>

<style scoped>
.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  min-height: 200px;
}

/* ===== SPINNER ===== */
.spinner {
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

/* ===== SIZES ===== */
.loading-sm .spinner {
  width: 24px;
  height: 24px;
  border-width: 2px;
}

.loading-md .spinner {
  width: 40px;
  height: 40px;
  border-width: 3px;
}

.loading-lg .spinner {
  width: 60px;
  height: 60px;
  border-width: 4px;
}

.loading-sm {
  padding: 1rem;
  min-height: 100px;
}

.loading-lg {
  padding: 3rem;
  min-height: 300px;
}

/* ===== TEXT ===== */
.loading-text {
  margin-top: 1rem;
  font-size: 0.9rem;
  font-weight: 500;
  text-align: center;
  font-family: var(--font-primary);
}

.loading-sm .loading-text {
  font-size: 0.8rem;
  margin-top: 0.75rem;
}

.loading-lg .loading-text {
  font-size: 1.1rem;
  margin-top: 1.25rem;
}

/* ===== VARIANTS ===== */

/* Default - pour fonds blancs/clairs */
.loading-default .spinner {
  border: 3px solid rgba(206, 94, 26, 0.2); /* terracotta avec opacité */
  border-top-color: var(--terracotta);
}

.loading-default .loading-text {
  color: var(--text-secondary);
}

/* Light - pour fonds colorés/sombres (texte blanc) */
.loading-light .spinner {
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: var(--cream);
}

.loading-light .loading-text {
  color: var(--cream);
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

/* Dark - pour fonds très clairs */
.loading-dark .spinner {
  border: 3px solid rgba(65, 36, 28, 0.1); /* coffee avec opacité */
  border-top-color: var(--coffee);
}

.loading-dark .loading-text {
  color: var(--coffee);
}

/* ===== ANIMATION ===== */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>