<template>
  <div class="step-content step-dates">
    <h3 class="step-title">Choisissez vos dates</h3>

    <div class="dates-layout">
      <!-- Calendrier -->
      <div class="calendar-section">
        <CameleonCalendar ref="calendar" :events="[]" :view="'dayGridMonth'" :selectable="true" :height="auto"
          :header-toolbar="headerToolbar" @date-select="onDateSelect" />
      </div>

      <!-- Infos -->
      <div class="booking-info">
        <!-- Dates sélectionnées - Mode range (hôtel) -->
        <div class="selected-dates" v-if="hasValidDates && isRangeMode">
          <div class="dates-display">
            <div class="date-item">
              <AppIcon name="calendar-plus" />
              <div>
                <label>Arrivée</label>
                <span>{{ formatDate(dates.start) }}</span>
              </div>
            </div>
            <div class="date-separator">
              <AppIcon name="arrow-right" />
            </div>
            <div class="date-item">
              <AppIcon name="calendar-minus" />
              <div>
                <label>Départ</label>
                <span>{{ formatDate(displayEndDate) }}</span>
              </div>
            </div>
          </div>
          <div class="nights-display">
            <AppIcon name="moon" />
            <span>{{ nights }} nuit{{ nights > 1 ? 's' : '' }}</span>
          </div>
        </div>

        <!-- Date sélectionnée - Mode single (traiteur) -->
        <div class="selected-dates" v-if="dates.start && !isRangeMode">
          <div class="date-item">
            <AppIcon name="calendar-check" />
            <div>
              <label>Date de la prestation</label>
              <span>{{ formatDate(dates.start) }}</span>
            </div>
          </div>

          <!-- Sélecteur d'heure -->
          <div class="time-selector">
            <label class="time-label">
              <AppIcon name="clock" />
              Heure souhaitée
            </label>
            <div class="time-inputs">
              <select class="time-select" :value="dates.hour || '12'" @change="updateTime('hour', $event.target.value)">
                <option v-for="h in hours" :key="h" :value="h">{{ h }}h</option>
              </select>
              <span class="time-sep">:</span>
              <select class="time-select" :value="dates.minute || '00'"
                @change="updateTime('minute', $event.target.value)">
                <option v-for="m in minutes" :key="m" :value="m">{{ m }}</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Convives -->
        <div class="guests-selector">
          <label class="guests-label">
            <AppIcon name="users" />
            {{ guestLabel }}
          </label>
          <div class="guests-controls">
            <button type="button" @click="updateGuests(dates.guests - 1)" :disabled="dates.guests <= 1"
              class="btn-guest-control">
              <AppIcon name="minus" />
            </button>
            <div class="guests-display">
              <span class="guests-number">{{ dates.guests }}</span>
              <span class="guests-text">personne{{ dates.guests > 1 ? 's' : '' }}</span>
            </div>
            <button type="button" @click="updateGuests(dates.guests + 1)" :disabled="dates.guests >= maxGuests"
              class="btn-guest-control">
              <AppIcon name="plus" />
            </button>
          </div>
          <small class="guests-hint">Maximum {{ maxGuests }} personnes</small>
        </div>

        <!-- Instructions -->
        <div class="step-instructions">
          <div class="instruction-item">
            <AppIcon name="mouse-pointer-click" />
            <span>Sélectionnez vos dates sur le calendrier</span>
          </div>
          <div class="instruction-item">
            <AppIcon name="info" />
            <span>Minimum 1 nuit — Réservation à partir de demain</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import CameleonCalendar from '@/shared/components/calendar/CameleonCalendar.vue'
import { useInstanceStore } from '@/shared/stores/instance'

export default {
  name: 'StepDates',
  components: { CameleonCalendar },

  props: {
    dates: {
      type: Object,
      required: true
      // Range: { start: '', endExclusive: '', guests: 2 }
      // Single: { start: '', endExclusive: '', guests: 2, hour: '12', minute: '00' }
    }
  },

  emits: ['update:dates'],

  data() {
    return {
      hours: Array.from({ length: 14 }, (_, i) => String(i + 8).padStart(2, '0')),
      // 08h à 21h
      minutes: ['00', '15', '30', '45']
    }
  },

  computed: {
    instance() {
      return useInstanceStore()
    },

    isRangeMode() {
      return this.instance.hasFeature('checkin_checkout')
    },

    guestLabel() {
      return this.instance.hasFeature('guest_count')
        ? 'Nombre de convives'
        : "Nombre d'invités"
    },

    maxGuests() {
      return this.instance.hasFeature('guest_count') ? 50 : 20
    },

    headerToolbar() {
      return { left: 'prev,next', center: 'title', right: 'today' }
    },

    minDate() {
      const d = new Date()
      d.setDate(d.getDate() + 1)
      return d.toISOString().split('T')[0]
    },

    displayEndDate() {
      if (!this.dates.endExclusive) return ''
      const e = new Date(this.dates.endExclusive)
      e.setDate(e.getDate() - 1)
      return e.toISOString().split('T')[0]
    },

    nights() {
      if (!this.isRangeMode) return 0
      if (!this.dates.start || !this.dates.endExclusive) return 0
      const start = new Date(this.dates.start)
      const end = new Date(this.dates.endExclusive)
      return Math.round((end - start) / (1000 * 60 * 60 * 24))
    },

    hasValidDates() {
      return this.nights > 0
    },

    isValid() {
      if (this.isRangeMode) {
        return this.hasValidDates && this.dates.guests >= 1
      }
      // Mode single : une date sélectionnée suffit
      return !!this.dates.start && this.dates.guests >= 1
    }
  },

  methods: {
    onDateSelect(info) {
      if (this.isRangeMode) {
        // Mode range : FullCalendar gère start/end
        this.$emit('update:dates', {
          ...this.dates,
          start: info.startStr,
          endExclusive: info.endStr
        })
      } else {
        // Mode single : on prend juste le jour cliqué
        this.$emit('update:dates', {
          ...this.dates,
          start: info.startStr,
          endExclusive: '',
          hour: this.dates.hour || '12',
          minute: this.dates.minute || '00'
        })
      }
    },

    updateTime(field, value) {
      this.$emit('update:dates', {
        ...this.dates,
        [field]: value
      })
    },

    updateGuests(value) {
      const clamped = Math.max(1, Math.min(this.maxGuests, value))
      this.$emit('update:dates', {
        ...this.dates,
        guests: clamped
      })
    },

    formatDate(s) {
      return s
        ? new Date(s).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
        : ''
    }
  }
}
</script>

<style scoped>
.step-title {
  color: var(--accent);
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 1rem;
  text-align: center;
}

.dates-layout {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 1.5rem;
  align-items: start;
}

.calendar-section {
  min-width: 0;
  -webkit-user-select: none;
  user-select: none;
}

.booking-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Dates sélectionnées */
.selected-dates {
  background: var(--bg-white);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
  padding: 1rem;
}

.dates-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.date-item :deep(.app-icon) {
  color: var(--accent);
}

.date-item div {
  display: flex;
  flex-direction: column;
}

.date-item label {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin: 0;
}

.date-item span {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.date-separator {
  color: var(--accent);
  padding: 0 0.25rem;
}

.nights-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  justify-content: center;
  background: var(--accent);
  color: white;
  padding: 0.5rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.9rem;
  margin-top: 0.75rem;
}

/* Convives */
.guests-selector {
  background: var(--bg-white);
  border: 1px solid var(--border-light);
  border-radius: var(--radius-sm);
  padding: 1rem;
}

.guests-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 1rem;
  font-size: 0.9rem;
}

.guests-label :deep(.app-icon) {
  color: var(--accent);
}

.guests-controls {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.btn-guest-control {
  width: 36px;
  height: 36px;
  border: 2px solid var(--accent);
  background: var(--bg-white);
  color: var(--accent);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-guest-control:hover:not(:disabled) {
  background: var(--accent);
  color: white;
}

.btn-guest-control:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.guests-display {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 80px;
}

.guests-number {
  font-size: 1.5rem;
  font-weight: bold;
  color: var(--accent);
  line-height: 1;
}

.guests-text {
  font-size: 0.8rem;
  color: var(--text-muted);
}

.guests-hint {
  text-align: center;
  color: var(--text-muted);
  font-size: 0.75rem;
  display: block;
}

/* Instructions */
.step-instructions {
  background: var(--bg-secondary);
  border-radius: 8px;
  padding: 1rem;
  border-left: 4px solid var(--info);
}

.instruction-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: 0.85rem;
}

.instruction-item:last-child {
  margin-bottom: 0;
}

.instruction-item :deep(.app-icon) {
  color: var(--info);
  width: 16px;
  text-align: center;
}

/* Time selector */
.time-selector {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-light);
}

.time-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}

.time-label :deep(.app-icon) {
  color: var(--accent);
}

.time-inputs {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.time-select {
  padding: 0.5rem 1rem;
  border: 2px solid var(--border-light);
  border-radius: 8px;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-primary);
  background: var(--bg-white);
  cursor: pointer;
  text-align: center;
  min-width: 80px;
  transition: border-color 0.2s ease;
}

.time-select:focus {
  outline: none;
  border-color: var(--accent);
}

.time-sep {
  font-size: 1.2rem;
  font-weight: 700;
  color: var(--accent);
}

/* Responsive */
@media (max-width: 768px) {
  .dates-layout {
    grid-template-columns: 1fr;
  }

  .dates-display {
    flex-direction: column;
    gap: 0.75rem;
  }

  .date-separator {
    transform: rotate(90deg);
  }
}
</style>
