<template>
    <transition name="modal-fade">
        <div v-if="show" class="modal-overlay" @click="closeModal" aria-modal="true" role="dialog">
            <div class="quote-modal" @click.stop>
                <!-- Header -->
                <div class="modal-header">
                    <h2 class="modal-title">
                        <i class="fas fa-calculator"></i>
                        Créer mon devis personnalisé
                    </h2>
                    <button @click="closeModal" class="btn-close" aria-label="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Contenu -->
                <div class="modal-content">
                    <!-- Étapes -->
                    <div class="progress-steps">
                        <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                            <div class="step-number">1</div><span>Dates & personnes</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                            <div class="step-number">2</div><span>Activités</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 3, completed: currentStep > 3 }">
                            <div class="step-number">3</div><span>Hébergement</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 4 }">
                            <div class="step-number">4</div><span>Récapitulatif</span>
                        </div>
                    </div>

                    <!-- STEP 1: Dates & personnes (FullCalendar only) -->
                    <div v-if="currentStep === 1" class="step-content">
                        <h3 class="step-title">Choisissez vos dates</h3>
                        <p class="step-description">Sélectionnez directement dans le calendrier (plage continue).</p>

                        <div class="dates-layout">
                            <!-- Calendrier -->
                            <div class="calendar-container">
                                <div class="calendar-header">
                                    <h4 class="calendar-title">
                                        <i class="fas fa-calendar-alt"></i> Disponibilités
                                    </h4>
                                    <div class="calendar-legend">
                                        <span class="legend-item"><span class="legend-box available"></span>Dispo</span>
                                        <span class="legend-item"><span
                                                class="legend-box unavailable"></span>Indispo</span>
                                        <span class="legend-item"><span class="legend-box selected"></span>Votre
                                            sélection</span>
                                    </div>
                                </div>
                                <FullCalendar :options="fcOptions" ref="fc" />
                                <div class="range-info" v-if="selectedDates.start && selectedDates.end">
                                    <i class="fas fa-calendar-check"></i>
                                    Séjour : {{ formatDate(selectedDates.start) }} → {{ formatDate(selectedDates.end) }}
                                </div>
                            </div>

                            <!-- Personnes -->
                            <div class="date-controls">
                                <div class="field-group">
                                    <label class="field-label">Nombre de personnes</label>
                                    <div class="qty">
                                        <button type="button" class="qty-btn" @click="decreaseGuests"
                                            aria-label="Diminuer">−</button>
                                        <span class="qty-value" aria-live="polite">{{ selectedDates.guests }}</span>
                                        <button type="button" class="qty-btn" @click="increaseGuests"
                                            aria-label="Augmenter">+</button>
                                    </div>
                                </div>

                                <div v-if="durationInDays > 0" class="duration">
                                    <i class="fas fa-moon"></i>
                                    {{ durationInDays }} nuit{{ durationInDays > 1 ? 's' : '' }}
                                </div>

                                <div v-if="hasUnavailableDay" class="alert warn">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Votre plage contient au moins un jour indisponible. Merci d’ajuster votre sélection.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Activités -->
                    <div v-if="currentStep === 2" class="step-content">
                        <h3 class="step-title">Choisissez vos activités</h3>
                        <p class="step-description">Sélectionnez les expériences qui vous tentent.</p>

                        <div v-if="loadingActivities" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des activités...</p>
                        </div>

                        <div v-else class="products-grid">
                            <div v-for="activity in availableActivities" :key="activity.id" class="product-card"
                                :class="{ selected: selectedItems.activities.some(a => a.id === activity.id) }"
                                @click="toggleActivity(activity)">
                                <div class="product-image">
                                    <img :src="activity.image" :alt="activity.name" />
                                    <div class="product-overlay">
                                        <i class="fas"
                                            :class="selectedItems.activities.some(a => a.id === activity.id) ? 'fa-check' : 'fa-plus'"></i>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h4>{{ activity.name }}</h4>
                                    <p class="price">{{ activity.formatted_price }}</p>
                                    <p class="description">{{ activity.short_description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Hébergement -->
                    <div v-if="currentStep === 3" class="step-content">
                        <h3 class="step-title">Choisissez votre hébergement</h3>
                        <p class="step-description">Sélectionnez votre logement.</p>

                        <div v-if="loadingRooms" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des hébergements...</p>
                        </div>

                        <div v-else class="products-grid">
                            <div v-for="room in availableRooms" :key="room.id" class="product-card"
                                :class="{ selected: selectedItems.room?.id === room.id }" @click="selectRoom(room)">
                                <div class="product-image">
                                    <img :src="room.image" :alt="room.name" />
                                    <div class="product-overlay">
                                        <i class="fas"
                                            :class="selectedItems.room?.id === room.id ? 'fa-check' : 'fa-plus'"></i>
                                    </div>
                                </div>
                                <div class="product-info">
                                    <h4>{{ room.name }}</h4>
                                    <p class="price">{{ room.formatted_price }}/nuit</p>
                                    <p class="description">{{ room.short_description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: Récapitulatif -->
                    <div v-if="currentStep === 4" class="step-content">
                        <h3 class="step-title">Récapitulatif de votre devis</h3>

                        <div class="quote-summary">
                            <div v-if="selectedItems.activities.length" class="summary-section">
                                <h4><i class="fas fa-hiking"></i> Activités</h4>
                                <div v-for="a in selectedItems.activities" :key="a.id" class="summary-item">
                                    <span>{{ a.name }}</span><span>{{ a.formatted_price }}</span>
                                </div>
                            </div>

                            <div v-if="selectedItems.room" class="summary-section">
                                <h4><i class="fas fa-bed"></i> Hébergement</h4>
                                <div class="summary-item">
                                    <span>{{ selectedItems.room.name }} ({{ durationInDays }} nuit{{ durationInDays > 1
                                        ? 's' : '' }})</span>
                                    <span>{{ formatPrice(selectedItems.room.price * durationInDays) }}</span>
                                </div>
                            </div>

                            <div class="summary-section">
                                <h4><i class="fas fa-calendar"></i> Détails du séjour</h4>
                                <div class="summary-item">
                                    <span>Du {{ formatDate(selectedDates.start) }} au {{ formatDate(selectedDates.end)
                                        }}</span>
                                </div>
                                <div class="summary-item">
                                    <span>{{ selectedDates.guests }} personne{{ selectedDates.guests > 1 ? 's' : ''
                                        }}</span>
                                </div>
                            </div>

                            <div class="summary-total">
                                <div class="total-line">
                                    <span>Total estimé</span>
                                    <strong>{{ formatPrice(totalPrice) }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Coordonnées -->
                        <div class="contact-form">
                            <h4>Vos coordonnées</h4>
                            <div class="form-row">
                                <input type="text" v-model="contactInfo.name" placeholder="Nom complet"
                                    class="form-input" />
                                <input type="email" v-model="contactInfo.email" placeholder="Email"
                                    class="form-input" />
                            </div>
                            <input type="tel" v-model="contactInfo.phone" placeholder="Téléphone" class="form-input" />
                            <textarea v-model="contactInfo.message" placeholder="Message (optionnel)"
                                class="form-textarea"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <div class="footer-left">
                        <button v-if="currentStep > 1" @click="previousStep" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Précédent
                        </button>
                    </div>
                    <div class="footer-right">
                        <button v-if="currentStep < 4" @click="nextStep" class="btn btn-primary"
                            :disabled="!canProceed">
                            Suivant <i class="fas fa-arrow-right"></i>
                        </button>
                        <button v-else @click="submitQuote" class="btn btn-success"
                            :disabled="isSubmitting || !canSubmit">
                            <i v-if="isSubmitting" class="fas fa-spinner fa-spin"></i>
                            <i v-else class="fas fa-paper-plane"></i>
                            {{ isSubmitting ? 'Envoi...' : 'Envoyer ma demande' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import { nextTick } from 'vue'
import { publicApi } from '@/services/PublicApi'

// FullCalendar vue3 + plugins (comme ton back-office)
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'

export default {
  name: 'QuoteModal',
  components: { FullCalendar },

  props: {
    show: { type: Boolean, default: false }
  },

  data() {
    return {
      // Étapes
      currentStep: 1,

      // Loaders
      loadingActivities: false,
      loadingRooms: false,
      availabilityLoading: false,
      isSubmitting: false,

      // Données produits
      availableActivities: [],
      availableRooms: [],

      // Sélections
      selectedItems: { activities: [], room: null },

      // Dates & personnes
      selectedDates: { start: '', end: '', guests: 2 },

      // Contact
      contactInfo: { name: '', email: '', phone: '', message: '' },

      // Dispos
      unavailableDates: new Set(), // ex: Set(['2025-10-03'])
      availability: { rooms: {}, activities: {} },
    }
  },

  computed: {
    // API du calendrier
    calendarApi() {
      return this.$refs.fc?.getApi?.()
    },

    // Date mini = demain
    minDate() {
      const d = new Date()
      d.setDate(d.getDate() + 1)
      return d.toISOString().split('T')[0]
    },

    durationInDays() {
      const { start, end } = this.selectedDates
      if (!start || !end) return 0
      const s = new Date(start), e = new Date(end)
      const diff = e - s
      return diff > 0 ? Math.ceil(diff / (1000 * 60 * 60 * 24)) : 0
    },

    totalPrice() {
      let total = 0
      for (const a of this.selectedItems.activities) total += Number(a.price || 0)
      if (this.selectedItems.room && this.durationInDays > 0) {
        total += Number(this.selectedItems.room.price || 0) * this.durationInDays
      }
      return total
    },

    hasUnavailableDay() {
      const { start, end } = this.selectedDates
      if (!start || !end || this.unavailableDates.size === 0) return false
      const s = new Date(start), e = new Date(end)
      for (let d = new Date(s); d < e; d.setDate(d.getDate() + 1)) {
        const k = d.toISOString().slice(0, 10)
        if (this.unavailableDates.has(k)) return true
      }
      return false
    },

    datesOK() {
      return !!this.selectedDates.start && !!this.selectedDates.end && this.durationInDays > 0 && !this.hasUnavailableDay
    },

    canProceed() {
      switch (this.currentStep) {
        case 1: return this.datesOK && this.selectedDates.guests >= 1
        case 2: return true // activités optionnelles
        case 3: return !!this.selectedItems.room
        default: return true
      }
    },

    canSubmit() {
      const c = this.contactInfo
      return !!c.name && !!c.email && !!c.phone && this.totalPrice > 0
    },

    // ⚙️ Options FullCalendar (réactives)
    fcOptions() {
      return {
        plugins: [dayGridPlugin, interactionPlugin],
        locales: [frLocale],
        locale: 'fr',
        initialView: 'dayGridMonth',
        firstDay: 1,
        height: 'auto',
        fixedWeekCount: false,
        headerToolbar: false,
        selectable: true,
        selectMirror: true,
        validRange: { start: this.minDate },
        selectAllow: this.fcSelectAllow,
        select: this.fcOnSelect,
        events: this.fcBuildEvents
      }
    }
  },

  watch: {
    show(val) {
      if (val) this.initializeModal()
      // pas besoin de “destroy” avec le composant vue
    },

    // Redessine la couche d’événements si la plage change
    'selectedDates.start'() { this.calendarApi?.refetchEvents() },
    'selectedDates.end'() { this.calendarApi?.refetchEvents() },

    // Si la dispo dépend des capacités, relance l’API
    'selectedDates.guests'() { this.refreshAvailability() },
  },

  methods: {
    async initializeModal() {
      this.currentStep = 1
      this.resetSelections()
      await Promise.all([this.loadActivities(), this.loadRooms()])
      await nextTick()
      // rien à init : <FullCalendar> est auto
    },

    async loadActivities() {
      this.loadingActivities = true
      try {
        const res = await publicApi.getActivities({ per_page: 20 })
        this.availableActivities = res?.data || []
      } catch (e) {
        console.error('Erreur chargement activités:', e)
        this.availableActivities = []
      } finally {
        this.loadingActivities = false
      }
    },

    async loadRooms() {
      this.loadingRooms = true
      try {
        const res = await publicApi.getRooms({ per_page: 20 })
        this.availableRooms = res?.data || []
      } catch (e) {
        console.error('Erreur chargement hébergements:', e)
        this.availableRooms = []
      } finally {
        this.loadingRooms = false
      }
    },

    // ==== Dispos ====
    async refreshAvailability() {
      const { start, end, guests } = this.selectedDates
      if (!start || !end || !guests) return

      this.availabilityLoading = true
      try {
        const { data } = await publicApi.getAvailability({ start, end, guests })
        this.unavailableDates = new Set(data?.unavailable_dates || [])
        this.availability.rooms = data?.rooms || {}
        this.availability.activities = data?.activities || {}

        if (this.hasUnavailableDay) {
          this.selectedDates.start = ''
          this.selectedDates.end = ''
          this.calendarApi?.unselect()
        }

        if (this.selectedItems.room && this.isRoomAvailable(this.selectedItems.room) === false) {
          this.selectedItems.room = null
        }
        this.selectedItems.activities = this.selectedItems.activities.filter(a => this.isActivityAvailable(a))

        this.calendarApi?.refetchEvents()
      } catch (e) {
        console.error('Erreur dispo:', e)
      } finally {
        this.availabilityLoading = false
      }
    },

    isActivityAvailable(a) {
      const map = this.availability.activities
      if (!map || typeof map !== 'object') return true
      return map[String(a.id)] !== false
    },

    isRoomAvailable(r) {
      const map = this.availability.rooms
      if (!map || typeof map !== 'object') return true
      return map[String(r.id)] !== false
    },

    // ==== Sélections ====
    toggleActivity(activity) {
      if (!this.isActivityAvailable(activity)) return
      const i = this.selectedItems.activities.findIndex(a => a.id === activity.id)
      if (i > -1) this.selectedItems.activities.splice(i, 1)
      else this.selectedItems.activities.push(activity)
    },

    selectRoom(room) {
      if (!this.isRoomAvailable(room)) return
      this.selectedItems.room = room
    },

    increaseGuests() { if (this.selectedDates.guests < 20) this.selectedDates.guests++ },
    decreaseGuests() { if (this.selectedDates.guests > 1)  this.selectedDates.guests-- },

    nextStep() { if (this.canProceed && this.currentStep < 4) this.currentStep++ },
    previousStep() { if (this.currentStep > 1) this.currentStep-- },

    async submitQuote() {
      if (!this.canSubmit) return
      this.isSubmitting = true
      try {
        const payload = {
          activities: this.selectedItems.activities.map(a => a.id),
          room: this.selectedItems.room?.id || null,
          dates: { ...this.selectedDates },
          contact: { ...this.contactInfo },
          total_price: this.totalPrice
        }
        await publicApi.createQuoteRequest(payload)
        this.$emit('quote-submitted', payload)
        this.closeModal()
        alert('Votre demande de devis a été envoyée avec succès !')
      } catch (e) {
        console.error('Erreur envoi devis:', e)
        alert('Une erreur est survenue. Veuillez réessayer.')
      } finally {
        this.isSubmitting = false
      }
    },

    closeModal() { this.$emit('close') },

    resetSelections() {
      this.selectedItems = { activities: [], room: null }
      this.selectedDates = { start: '', end: '', guests: 2 }
      this.contactInfo = { name: '', email: '', phone: '', message: '' }
      this.unavailableDates = new Set()
      this.availability = { rooms: {}, activities: {} }
      this.calendarApi?.unselect?.()
      this.calendarApi?.refetchEvents?.()
    },

    formatPrice(n) { return new Intl.NumberFormat('fr-FR',{style:'currency',currency:'EUR'}).format(Number(n||0)) },
    formatDate(s) { return s ? new Date(s).toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'}) : '' },

    // ==== FullCalendar (via <FullCalendar/>)
    fcSelectAllow(arg) {
      const start = new Date(arg.start)
      const end = new Date(arg.end); end.setDate(end.getDate() - 1) // end EXCLUS
      for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
        const key = d.toISOString().slice(0,10)
        if (this.unavailableDates.has(key)) return false
      }
      return true
    },

    fcOnSelect(info) {
      const start = info.startStr
      const endDate = new Date(info.end); endDate.setDate(endDate.getDate() - 1) // end EXCLUS
      const end = endDate.toISOString().slice(0,10)
      this.selectedDates.start = start
      this.selectedDates.end = end
      this.refreshAvailability()
      this.calendarApi?.refetchEvents()
    },

    fcBuildEvents(fetchInfo, success) {
      const evts = []
      // indispos
      this.unavailableDates.forEach(d => {
        evts.push({ start: d, end: d, display: 'background', classNames: ['fc-day-unavailable'] })
      })
      // sélection actuelle
      if (this.selectedDates.start && this.selectedDates.end) {
        const end = new Date(this.selectedDates.end); end.setDate(end.getDate() + 1) // end EXCLUS
        evts.push({
          start: this.selectedDates.start,
          end: end.toISOString().slice(0,10),
          display: 'background',
          classNames: ['fc-day-selected-range']
        })
      }
      success(evts)
    }
  }
}
</script>


<style lang="scss" scoped>
// Variables reprises de votre design system
$primary: #5e72e4;
$success: #2dce89;
$warning: #fb6340;
$danger: #f5365c;
$terracotta: #c17c4a;

/* 1) Neutraliser tout layout grid/flex hérités dans la modale */
.modal-content {
  display: block !important;        /* évite une grille à 2 colonnes héritée */
  padding: 1rem 1.25rem;
}

/* 2) Forcer le panneau d’étape à prendre toute la largeur (et virer les bordures de "panel") */
.step-content {
  width: 100% !important;
  max-width: none !important;
  margin: 0 auto !important;
  border: 0 !important;
  box-shadow: none !important;
  background: transparent;
}

/* 3) Grille de l’étape Dates: par défaut 1 colonne (pas d’espace réservé à droite) */
.dates-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.25rem 1.5rem;
  align-items: start;
}

/* Taille & intégration */
.calendar {
    width: 100%;
    max-width: 850px;
    margin: 0 auto 1rem;
    border-radius: 12px;
    overflow: hidden;
    background: var(--bg-glass, rgba(255, 255, 255, 0.6));
    backdrop-filter: blur(8px);
}

.quote-modal {
    max-width: 900px;
    /* large mais pas plein écran */
    width: 95%;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Jours indisponibles (events background) */
.fc-daygrid-day.fc-day-unavailable,
.fc .fc-daygrid-day-bg .fc-day-unavailable {
    background: repeating-linear-gradient(45deg, rgba(255, 0, 0, 0.15), rgba(255, 0, 0, 0.15) 6px, transparent 6px, transparent 12px);
}

/* Plage sélectionnée */
.fc .fc-daygrid-day-bg .fc-day-selected-range {
    background: rgba(123, 97, 255, 0.18);
}

/* Surbrillance native de la sélection */
.fc .fc-highlight {
    background: rgba(123, 97, 255, 0.22);
}

/* Header/legend */
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .5rem;
}

.calendar-legend {
    display: flex;
    gap: .75rem;
    font-size: .85rem;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
}

.legend-box {
    width: 14px;
    height: 12px;
    border-radius: 3px;
    display: inline-block;
}

.legend-box.available {
    background: rgba(0, 200, 120, .3);
}

.legend-box.unavailable {
    background: rgba(255, 0, 0, .25);
}

.legend-box.selected {
    background: rgba(123, 97, 255, .35);
}

/* Petites infos */
.range-info,
.duration {
    margin-top: .5rem;
    display: flex;
    align-items: center;
    gap: .5rem;
}

.alert.warn {
    color: #b45309;
    background: rgba(251, 191, 36, .15);
    padding: .5rem .75rem;
    border-radius: 8px;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    gap: 1rem;
}

.progress-steps .step {
    flex: 1;
    text-align: center;
    position: relative;
    font-size: 0.9rem;
    color: #666;
}

.progress-steps .step-number {
    width: 28px;
    height: 28px;
    margin: 0 auto 0.25rem;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.progress-steps .step.active .step-number {
    background: #6366f1;
    /* violet */
    color: #fff;
}

.progress-steps .step.completed .step-number {
    background: #10b981;
    /* vert */
    color: #fff;
}
/* Jours indisponibles */
:deep(.fc .fc-daygrid-day-bg .fc-day-unavailable),
:deep(.fc-daygrid-day.fc-day-unavailable) {
  background: repeating-linear-gradient(
    45deg,
    rgba(255,0,0,0.15),
    rgba(255,0,0,0.15) 6px,
    transparent 6px,
    transparent 12px
  );
}

/* Plage sélectionnée */
:deep(.fc .fc-daygrid-day-bg .fc-day-selected-range) {
  background: rgba(123,97,255,.18);
}

/* Surbrillance native */
:deep(.fc .fc-highlight) {
  background: rgba(123,97,255,.22);
}

</style>