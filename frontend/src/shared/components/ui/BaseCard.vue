<!-- src/shared/components/ui/BaseCard.vue -->
<template>
  <div :class="cardClasses">
    <!-- Header de la carte -->
    <div v-if="$slots.header || title || actions" class="card-header">
      <div class="card-header-content">
        <!-- Slot header personnalisé ou titre par défaut -->
        <slot name="header">
          <div v-if="title" class="card-title-section">
            <h3 class="card-title">
              <i v-if="icon" :class="iconClasses"></i>
              {{ title }}
              <small v-if="subtitle" class="card-subtitle">{{ subtitle }}</small>
            </h3>
            <p v-if="description" class="card-description">{{ description }}</p>
          </div>
        </slot>
      </div>
      
      <!-- Actions dans le header -->
      <div v-if="$slots.actions" class="card-header-actions">
        <slot name="actions"></slot>
      </div>
    </div>
    
    <!-- Corps de la carte -->
    <div :class="bodyClasses">
      <slot></slot>
    </div>
    
    <!-- Footer de la carte -->
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: 'BaseCard',
  
  props: {
    title: String,
    subtitle: String,
    description: String,
    icon: String,
    iconColor: String,
    variant: {
      type: String,
      default: 'default',
      validator: v => ['default', 'bordered', 'elevated', 'flat'].includes(v)
    },
    padding: {
      type: String,
      default: 'md',
      validator: v => ['none', 'sm', 'md', 'lg'].includes(v)
    },
    hover: {
      type: Boolean,
      default: false
    }
  },
  
  computed: {
    cardClasses() {
      return [
        'card',
        `card-${this.variant}`,
        {
          'card-hover': this.hover
        }
      ]
    },
    
    bodyClasses() {
      return [
        'card-body',
        `card-body-${this.padding}`
      ]
    },
    
    iconClasses() {
      return [
        this.icon,
        'card-icon',
        {
          'card-icon-colored': this.iconColor
        }
      ]
    }
  }
}
</script>

<style scoped>
.card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s ease;
}

.card-default {
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-bordered {
  border: 2px solid #e5e7eb;
}

.card-elevated {
  border: none;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.card-flat {
  border: none;
  box-shadow: none;
  background: #f9fafb;
}

.card-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Header */
.card-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 1.5rem 1.5rem 0 1.5rem;
}

.card-header-content {
  flex: 1;
}

.card-header-actions {
  display: flex;
  gap: 0.5rem;
  margin-left: 1rem;
}

.card-title-section {
  margin-bottom: 1rem;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-primary);
  line-height: 1.2;
}

.card-icon {
  color: var(--primary);
  font-size: 1.1em;
}

.card-icon-colored {
  color: v-bind(iconColor);
}

.card-subtitle {
  font-size: 0.875rem;
  font-weight: 400;
  color: #6b7280;
  margin-left: 0.5rem;
}

.card-description {
  margin: 0.5rem 0 0 0;
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.4;
}

/* Body */
.card-body {
  color: var(--text-primary);
}

.card-body-none {
  padding: 0;
}

.card-body-sm {
  padding: 1rem;
}

.card-body-md {
  padding: 1.5rem;
}

.card-body-lg {
  padding: 2rem;
}

/* Footer */
.card-footer {
  padding: 1rem 1.5rem 1.5rem 1.5rem;
  border-top: 1px solid #f3f4f6;
  background: #f9fafb;
}
</style>