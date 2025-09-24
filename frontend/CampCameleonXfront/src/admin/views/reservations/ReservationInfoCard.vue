<template>
  <section class="reservation-info-card info-section">
    <header v-if="hasHeader" class="info-card-header">
      <div class="info-card-title">
        <i v-if="icon" :class="['info-card-icon', icon]"></i>
        <div class="title-group">
          <h3 v-if="title" class="info-card-heading">{{ title }}</h3>
          <p v-if="subtitle" class="info-card-subtitle">{{ subtitle }}</p>
        </div>
      </div>
      <div v-if="$slots.actions" class="info-card-actions">
        <slot name="actions"></slot>
      </div>
    </header>
    <div class="info-card-body">
      <slot />
    </div>
  </section>
</template>

<script>
export default {
  name: 'ReservationInfoCard',
  props: {
    title: {
      type: String,
      default: ''
    },
    subtitle: {
      type: String,
      default: ''
    },
    icon: {
      type: String,
      default: ''
    }
  },
  computed: {
    hasHeader() {
      return Boolean(this.title || this.subtitle || this.icon || this.$slots.actions)
    }
  }
}
</script>

<style scoped>
.reservation-info-card {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background: var(--bg-white);
  border-radius: 1rem;
  box-shadow: var(--shadow);
  border: 1px solid rgba(65, 36, 28, 0.08);
  padding: 1.75rem;
}

.info-card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1.5rem;
  border-bottom: 1px solid rgba(65, 36, 28, 0.08);
  padding-bottom: 1rem;
}

.info-card-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.info-card-icon {
  font-size: 1.5rem;
  color: var(--primary);
}

.info-card-heading {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
}

.info-card-subtitle {
  margin: 0.25rem 0 0;
  color: var(--text-muted);
  font-size: 0.95rem;
}

.info-card-actions {
  display: flex;
  gap: 0.75rem;
}

.info-card-body {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

@media (max-width: 768px) {
  .reservation-info-card {
    padding: 1.25rem;
  }

  .info-card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .info-card-actions {
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
  }
}
</style>