<!-- src/admin/views/products/components/CategoryBadge.vue -->
<template>
  <span v-if="category" class="category-badge" :class="`type-${category.type.toLowerCase()}`">
    {{ category.name }}
  </span>
</template>

<script>
export default {
  name: 'CategoryBadge',
  props: {
    category: {
      type: Object,
      required: true
    }
  }
}
</script>

<style scoped>
.category-badge {
  display: inline-block;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: white;
}

.type-activity { background-color: #3b82f6; }
.type-menu { background-color: #10b981; }
.type-dish { background-color: #f97316; }
.type-room { background-color: #f59e0b; }
.type-ingredient { background-color: #22c55e; }
</style>