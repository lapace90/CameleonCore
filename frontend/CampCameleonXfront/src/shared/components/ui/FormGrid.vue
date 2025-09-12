<template>
  <div :class="gridClasses">
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: 'FormGrid',
  props: {
    columns: {
      type: Number,
      default: 2
    },
    gap: {
      type: String,
      default: '1.5rem'
    }
  },
  
  computed: {
    gridClasses() {
      return [
        'form-grid',
        `form-grid-${this.columns}`
      ]
    }
  }
}
</script>

<style scoped>
.form-grid {
  display: grid;
  gap: v-bind(gap);
}

.form-grid-1 {
  grid-template-columns: 1fr;
}

.form-grid-2 {
  grid-template-columns: 1fr 1fr;
}

.form-grid-3 {
  grid-template-columns: repeat(3, 1fr);
}

.form-grid-4 {
  grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 768px) {
  .form-grid-2,
  .form-grid-3,
  .form-grid-4 {
    grid-template-columns: 1fr;
  }
}
</style>