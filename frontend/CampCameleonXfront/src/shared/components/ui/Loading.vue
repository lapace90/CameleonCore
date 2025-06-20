<template>
  <div class="loading" :class="sizeClass">
    <div class="loading-spinner">
      <div class="spinner-ring"></div>
      <div class="spinner-ring"></div>
      <div class="spinner-ring"></div>
    </div>
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
    }
  },
  computed: {
    sizeClass() {
      return `loading-${this.size}`;
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
}

.loading-spinner {
  position: relative;
  display: inline-block;
}

.spinner-ring {
  display: inline-block;
  border-radius: 50%;
  border: 2px solid transparent;
  border-top: 2px solid #5e72e4;
  animation: spin 1s linear infinite;
  position: absolute;
  top: 0;
  left: 0;
}

.loading-sm .spinner-ring {
  width: 20px;
  height: 20px;
}

.loading-md .spinner-ring {
  width: 40px;
  height: 40px;
}

.loading-lg .spinner-ring {
  width: 60px;
  height: 60px;
}

.spinner-ring:nth-child(1) {
  animation-delay: 0s;
}

.spinner-ring:nth-child(2) {
  animation-delay: 0.1s;
  border-top-color: #2dce89;
}

.spinner-ring:nth-child(3) {
  animation-delay: 0.2s;
  border-top-color: #fb6340;
}

.loading-text {
  margin-top: 1rem;
  color: #8898aa;
  font-size: 0.875rem;
}

.loading-sm .loading-text {
  font-size: 0.75rem;
}

.loading-lg .loading-text {
  font-size: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>