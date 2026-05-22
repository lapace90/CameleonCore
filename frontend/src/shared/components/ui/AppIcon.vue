<template>
  <component
    :is="iconComponent"
    :size="size"
    :stroke-width="strokeWidth"
    :class="['app-icon', { 'icon-spin': spin }]"
  />
</template>

<script setup>
import { computed } from 'vue'
import * as icons from 'lucide-vue-next'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: [Number, String], default: 20 },
  strokeWidth: { type: [Number, String], default: 2 },
  spin: { type: Boolean, default: false }
})

// Maps FA icon names that differ from Lucide equivalents after prefix stripping
const FA_OVERRIDES = {
  // No direct Lucide match
  'hiking': 'footprints',
  'walking': 'footprints',
  'drumstick-bite': 'utensils',
  'pepper-hot': 'flame',
  'glass-milk': 'glass-water',
  'water': 'waves',
  'fire': 'flame',
  'tree': 'tree-pine',
  'user-friends': 'users',
  'cogs': 'settings',
  'bell-concierge': 'bell',
  'chart-line': 'trending-up',
  'comments': 'message-circle',
  'ellipsis-h': 'ellipsis',
  'ellipsis-v': 'ellipsis-vertical',
  // Renamed in Lucide
  'times': 'x',
  'times-circle': 'circle-x',
  'check-circle': 'circle-check',
  'exclamation-circle': 'circle-alert',
  'exclamation-triangle': 'triangle-alert',
  'info-circle': 'info',
  'question-circle': 'circle-help',
  'spinner': 'loader-circle',
  'sort': 'arrow-up-down',
  'sort-up': 'chevron-up',
  'sort-down': 'chevron-down',
}

function normalizeName(raw) {
  if (!raw) return 'circle-help'
  // Strip FA prefix: 'fas fa-leaf' → 'leaf', 'fa-leaf' → 'leaf'
  const cleaned = raw.trim()
    .replace(/^fa[srldb]?\s+fa-/, '')
    .replace(/^fa-/, '')
  return FA_OVERRIDES[cleaned] || cleaned
}

function toPascal(str) {
  return str.split('-').map(s => s.charAt(0).toUpperCase() + s.slice(1)).join('')
}

const iconComponent = computed(() => {
  const name = toPascal(normalizeName(props.name))
  return icons[name] || icons['CircleHelp']
})
</script>

<style scoped>
.app-icon {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}
.icon-spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
