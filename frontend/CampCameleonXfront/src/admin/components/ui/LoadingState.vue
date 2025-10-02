<template>
  <div v-if="shouldShow" :class="containerClass">
    <!-- Loading State -->
    <div v-if="state === 'loading'" class="state-content state-loading">
      <div class="spinner" :class="spinnerSize"></div>
      <p v-if="loadingText" class="state-text">{{ loadingText }}</p>
      <slot name="loading" />
    </div>

    <!-- Error State -->
    <div v-else-if="state === 'error'" class="state-content state-error">
      <i :class="['state-icon', errorIcon]"></i>
      <h3 v-if="errorTitle" class="state-title">{{ errorTitle }}</h3>
      <p class="state-text">{{ errorMessage }}</p>
      <button 
        v-if="showRetry" 
        @click="$emit('retry')" 
        class="btn btn-outline btn-sm"
      >
        <i class="fas fa-redo"></i>
        {{ retryLabel }}
      </button>
      <slot name="error" :message="errorMessage" />
    </div>

    <!-- Empty State -->
    <div v-else-if="state === 'empty'" class="state-content state-empty">
      <i :class="['state-icon', emptyIcon]"></i>
      <h3 v-if="emptyTitle" class="state-title">{{ emptyTitle }}</h3>
      <p class="state-text">{{ emptyMessage }}</p>
      <button 
        v-if="showAction" 
        @click="$emit('action')" 
        class="btn btn-primary btn-sm"
      >
        <i v-if="actionIcon" :class="actionIcon"></i>
        {{ actionLabel }}
      </button>
      <slot name="empty" />
    </div>

    <!-- Success State (optionnel) -->
    <div v-else-if="state === 'success'" class="state-content state-success">
      <i :class="['state-icon', successIcon]"></i>
      <h3 v-if="successTitle" class="state-title">{{ successTitle }}</h3>
      <p class="state-text">{{ successMessage }}</p>
      <slot name="success" />
    </div>
  </div>
</template>

<script>
export default {
  name: 'LoadingState',

  props: {
    // État actuel : 'loading' | 'error' | 'empty' | 'success' | null
    state: {
      type: String,
      default: null,
      validator: value => [null, 'loading', 'error', 'empty', 'success'].includes(value)
    },

    // Variante visuelle
    variant: {
      type: String,
      default: 'card',
      validator: value => ['card', 'inline', 'fullscreen', 'minimal'].includes(value)
    },

    // Taille du spinner
    size: {
      type: String,
      default: 'md',
      validator: value => ['sm', 'md', 'lg'].includes(value)
    },

    // Loading
    loadingText: {
      type: String,
      default: 'Chargement...'
    },

    // Error
    errorTitle: {
      type: String,
      default: null
    },
    errorMessage: {
      type: String,
      default: 'Une erreur est survenue'
    },
    errorIcon: {
      type: String,
      default: 'fas fa-exclamation-triangle'
    },
    showRetry: {
      type: Boolean,
      default: true
    },
    retryLabel: {
      type: String,
      default: 'Réessayer'
    },

    // Empty
    emptyTitle: {
      type: String,
      default: 'Aucun résultat'
    },
    emptyMessage: {
      type: String,
      default: ''
    },
    emptyIcon: {
      type: String,
      default: 'fas fa-inbox'
    },
    showAction: {
      type: Boolean,
      default: false
    },
    actionLabel: {
      type: String,
      default: 'Créer'
    },
    actionIcon: {
      type: String,
      default: 'fas fa-plus'
    },

    // Success
    successTitle: {
      type: String,
      default: 'Succès'
    },
    successMessage: {
      type: String,
      default: ''
    },
    successIcon: {
      type: String,
      default: 'fas fa-check-circle'
    }
  },

  emits: ['retry', 'action'],

  computed: {
    shouldShow() {
      return this.state !== null
    },

    containerClass() {
      return [
        'loading-state',
        `loading-state--${this.variant}`,
        `loading-state--${this.state}`
      ]
    },

    spinnerSize() {
      return `spinner--${this.size}`
    }
  }
}
</script>

<style scoped>
/* =============================
   Container Variants
   ============================= */

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

/* Card variant (par défaut) - avec fond et padding */
.loading-state--card {
  background: #ffffff;
  border-radius: 12px;
  padding: 4rem 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
  min-height: 300px;
}

/* Inline variant - sans fond, compact */
.loading-state--inline {
  padding: 2rem 1rem;
  min-height: auto;
}

/* Fullscreen variant - prend tout l'écran */
.loading-state--fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  z-index: 9999;
  padding: 2rem;
}

/* Minimal variant - ultra compact */
.loading-state--minimal {
  padding: 1rem;
  min-height: auto;
}

/* =============================
   State Content
   ============================= */

.state-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  width: 100%;
  max-width: 500px;
}

/* =============================
   Spinner
   ============================= */

.spinner {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #5e72e4;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

.spinner--sm {
  width: 24px;
  height: 24px;
  border-width: 2px;
}

.spinner--md {
  width: 40px;
  height: 40px;
  border-width: 3px;
}

.spinner--lg {
  width: 60px;
  height: 60px;
  border-width: 4px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* =============================
   Icons
   ============================= */

.state-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.state-loading .state-icon {
  color: #5e72e4;
}

.state-error .state-icon {
  color: #f5365c;
}

.state-empty .state-icon {
  color: #8898aa;
}

.state-success .state-icon {
  color: #2dce89;
}

/* =============================
   Text Content
   ============================= */

.state-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #32325d;
  margin: 0 0 0.5rem 0;
}

.state-text {
  font-size: 0.9375rem;
  color: #8898aa;
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

/* =============================
   State-specific styles
   ============================= */

/* Loading */
.state-loading .state-text {
  color: #5e72e4;
  font-weight: 500;
}

/* Error */
.state-error .state-title {
  color: #f5365c;
}

/* Empty */
.state-empty .state-text {
  max-width: 400px;
}

/* Success */
.state-success .state-title {
  color: #2dce89;
}

/* =============================
   Responsive
   ============================= */

@media (max-width: 768px) {
  .loading-state--card {
    padding: 3rem 1.5rem;
    min-height: 250px;
  }

  .state-icon {
    font-size: 2.5rem;
  }

  .state-title {
    font-size: 1.125rem;
  }

  .state-text {
    font-size: 0.875rem;
  }
}

@media (max-width: 480px) {
  .loading-state--card {
    padding: 2rem 1rem;
    min-height: 200px;
  }

  .state-icon {
    font-size: 2rem;
  }

  .spinner--lg {
    width: 48px;
    height: 48px;
  }
}
</style>