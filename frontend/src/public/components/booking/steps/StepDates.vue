<template>
  <div class="step-content step-dates">
    <h3 class="step-title">Choisissez vos dates</h3>

    <div class="dates-layout">
      <!-- Calendrier -->
      <div class="calendar-section">
        <CameleonCalendar
          ref="calendar"
          :events="[]"
          :view="'dayGridMonth'"
          :selectable="true"
          :height="380"
          :header-toolbar="headerToolbar"
          @date-select="onDateSelect"
        />
      </div>

      <!-- Infos -->
      <div class="booking-info">
        <!-- Dates sélectionnées -->
        <div class="selected-dates" v-if="hasValidDates">
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

        <!-- Convives -->
        <div class="guests-selector">
          <label class="guests-label">
            <AppIcon name="users" />
            {{ guestLabel }}
          </label>
          <div class="guests-controls">
            <button type="button" @click="updateGuests(dates.guests - 1)" :disabled="dates.guests <= 1" class="btn-guest-control">
              <AppIcon name="minus" />
            </button>
            <div class="guests-display">
              <span class="guests-number">{{ dates.guests }}</span>
              <span class="guests-text">personne{{ dates.guests > 1 ? 's' : '' }}</span>
            </div>
            <button type="button" @click="updateGuests(dates.guests + 1)" :disabled="dates.guests >= maxGuests" class="btn-guest-control">
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
      // { start: '', endExclusive: '', guests: 2 }
    }
  },

  emits: ['update:dates'],

  computed: {
    instance() {
      return useInstanceStore()
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
      if (!this.dates.start || !this.dates.endExclusive) return 0
      const start = new Date(this.dates.start)
      const end = new Date(this.dates.endExclusive)
      return Math.round((end - start) / (1000 * 60 * 60 * 24))
    },

    hasValidDates() {
      return this.nights > 0
    },

    isValid() {
      return this.hasValidDates && this.dates.guests >= 1
    }
  },

  methods: {
    onDateSelect(info) {
      this.$emit('update:dates', {
        ...this.dates,
        start: info.startStr,
        endExclusive: info.endStr
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
