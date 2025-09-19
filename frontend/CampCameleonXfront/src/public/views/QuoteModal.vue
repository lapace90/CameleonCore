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
                <div class="modal-body">
                    <!-- Étapes -->
                    <div class="progress-steps">
                        <div class="step" :class="{ active: currentStep === 1, completed: currentStep > 1 }">
                            <div class="step-number">1</div><span>Dates & personnes</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 2, completed: currentStep > 2 }">
                            <div class="step-number">2</div><span>Activités</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 3, completed: currentStep > 3 }">
                            <div class="step-number">3</div><span>Menus</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 4, completed: currentStep > 4 }">
                            <div class="step-number">4</div><span>Hébergement</span>
                        </div>
                        <div class="step" :class="{ active: currentStep === 5 }">
                            <div class="step-number">5</div><span>Récapitulatif</span>
                        </div>
                    </div>

                    <!-- STEP 1 -->
                    <div v-if="currentStep === 1" class="step-content">
                        <h3 class="step-title">Choisissez vos dates</h3>
                        <p class="step-description">Sélectionnez directement dans le calendrier (plage continue).</p>
                        <div class="step-help" :class="{ 'help-warning': currentStep === 4 && !selectedItems.room }">
                            <i class="fas fa-info-circle"></i>
                            {{ stepHelpText }}
                        </div>

                        <div class="dates-layout">
                            <!-- Calendrier simplifié -->
                            <div class="calendar-container">
                                <div class="calendar-header">
                                    <h4 class="calendar-title">
                                        <i class="fas fa-calendar-alt"></i> Sélectionnez vos dates
                                    </h4>
                                </div>
                                <div class="calendar calendar--compact">
                                    <FullCalendar :options="fcOptions" ref="fc" />
                                </div>

                                <div class="range-info" v-if="selectedDates.start && selectedDates.endExclusive">
                                    <i class="fas fa-calendar-check"></i>
                                    Séjour : {{ formatDate(selectedDates.start) }} →
                                    {{ formatDate(displayEndInclusive(selectedDates.endExclusive)) }}
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

                                <div v-if="nights > 0" class="duration">
                                    <i class="fas fa-moon"></i>
                                    {{ nights }} nuit{{ nights > 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Activités -->
                    <div v-if="currentStep === 2" class="step-content">
                        <h3 class="step-title">Choisissez vos activités</h3>
                        <p class="step-description">Sélectionnez les expériences qui vous tentent.</p>

                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des activités...</p>
                        </div>

                        <div v-else class="mini-grid">
                            <article v-for="activity in availableActivities" :key="activity.id" class="mini-card"
                                :class="{ selected: selectedItems.activities.some(a => a.id === activity.id) }"
                                @click="toggleActivity(activity)">
                                <img class="mini-thumb" :src="activity.image" :alt="activity.name" loading="lazy"
                                    decoding="async" />
                                <div class="mini-info">
                                    <h5 class="mini-title">{{ activity.name }}</h5>
                                    <div class="mini-meta">
                                        <span class="mini-price">{{ activity.formatted_price }}</span>
                                    </div>
                                    <p class="mini-desc">
                                        {{ (activity.description || '').slice(0, 90) }}<span
                                            v-if="(activity.description || '').length > 90">…</span>
                                    </p>
                                </div>
                                <div class="mini-check">
                                    <i class="fas"
                                        :class="selectedItems.activities.some(a => a.id === activity.id) ? 'fa-check' : 'fa-plus'"></i>
                                </div>
                            </article>
                        </div>
                    </div>

                    <!-- STEP 3: Menus -->
                    <div v-if="currentStep === 3" class="step-content">
                        <h3 class="step-title">Choisissez vos menus</h3>
                        <p class="step-description">Sélectionnez les repas qui vous intéressent.</p>

                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des menus...</p>
                        </div>

                        <div v-else class="mini-grid">
                            <article v-for="menu in availableMenus" :key="menu.id" class="mini-card"
                                :class="{ selected: selectedItems.menus.some(m => m.id === menu.id) }"
                                @click="toggleMenu(menu)">
                                <img class="mini-thumb" :src="menu.image" :alt="menu.name" loading="lazy"
                                    decoding="async" />
                                <div class="mini-info">
                                    <h5 class="mini-title">{{ menu.name }}</h5>
                                    <div class="mini-meta">
                                        <span class="mini-price">{{ menu.formatted_price }}</span>
                                    </div>
                                    <p class="mini-desc">
                                        {{ (menu.description || menu.short_description || '').slice(0, 90) }}<span
                                            v-if="(menu.description || menu.short_description || '').length > 90">…</span>
                                    </p>
                                </div>
                                <div class="mini-check">
                                    <i class="fas"
                                        :class="selectedItems.menus.some(m => m.id === menu.id) ? 'fa-check' : 'fa-plus'"></i>
                                </div>
                            </article>
                        </div>
                    </div>

                    <!-- STEP 4: Hébergement -->
                    <div v-if="currentStep === 4" class="step-content">
                        <h3 class="step-title">Choisissez votre hébergement</h3>
                        <p class="step-description">Sélectionnez votre logement.</p>

                        <div v-if="loading" class="loading-state">
                            <div class="spinner"></div>
                            <p>Chargement des hébergements...</p>
                        </div>

                        <div v-else class="mini-grid">
                            <article v-for="room in availableRooms" :key="room.id" class="mini-card"
                                :class="{ selected: selectedItems.room?.id === room.id }" @click="selectRoom(room)">
                                <img class="mini-thumb" :src="room.image" :alt="room.name" loading="lazy"
                                    decoding="async" />
                                <div class="mini-info">
                                    <h5 class="mini-title">{{ room.name }}</h5>
                                    <div class="mini-meta">
                                        <span class="mini-price">{{ room.formatted_price }}/nuit</span>
                                        <!-- ✅ SEULE INFO PRODUCTABLE : Capacité -->
                                        <span v-if="room.productable_data?.capacity || room.productableData?.capacity"
                                            class="mini-pill">
                                            <i class="fas fa-users"></i>
                                            {{ room.productable_data?.capacity || room.productableData?.capacity }}
                                            pers. max
                                        </span>
                                    </div>
                                    <p class="mini-desc">
                                        {{ (room.description || room.short_description || '').slice(0, 90) }}<span
                                            v-if="(room.description || room.short_description || '').length > 90">…</span>
                                    </p>
                                </div>
                                <div class="mini-check">
                                    <i class="fas"
                                        :class="selectedItems.room?.id === room.id ? 'fa-check' : 'fa-plus'"></i>
                                </div>
                            </article>
                        </div>
                    </div>

                    <!-- STEP 5: Récap -->
                    <div v-if="currentStep === 5" class="step-content">
                        <h3 class="step-title">Récapitulatif de votre devis</h3>

                        <div class="quote-summary">
                            <!-- ACTIVITÉS -->
                            <div v-if="pricing.lines.some(l => l.type === 'activity')" class="summary-section">
                                <h4><i class="fas fa-hiking"></i> Activités</h4>
                                <div v-for="l in pricing.lines.filter(l => l.type === 'activity')" :key="'a-' + l.id"
                                    class="summary-item">
                                    <span>
                                        {{ l.name }}
                                        <select class="qty-select" :value="getQty('activity', l.id, l.qty)"
                                            @change="setQty('activity', l.id, $event.target.value)">
                                            <option v-for="n in getLineMaxQty('activity')" :key="n" :value="n - 1">{{ n
                                                - 1
                                                }}</option>
                                            <option :value="getLineMaxQty('activity')">{{ getLineMaxQty('activity') }}
                                            </option>
                                        </select>
                                        <small v-if="getQty('activity', l.id, l.qty) !== l.qty"
                                            class="muted">(ajusté)</small>
                                    </span>
                                    <span>{{ formatPrice(l.unit * getQty('activity', l.id, l.qty)) }}</span>
                                </div>
                            </div>

                            <!-- MENUS -->
                            <div v-if="pricing.lines.some(l => l.type === 'menu')" class="summary-section">
                                <h4><i class="fas fa-utensils"></i> Menus</h4>
                                <div v-for="l in pricing.lines.filter(l => l.type === 'menu')" :key="'m-' + l.id"
                                    class="summary-item">
                                    <span>
                                        {{ l.name }}
                                        <select class="qty-select" :value="getQty('menu', l.id, l.qty)"
                                            @change="setQty('menu', l.id, $event.target.value)">
                                            <option value="0">0</option>
                                            <option v-for="n in getLineMaxQty('menu')" :key="n" :value="n">{{ n }}
                                            </option>
                                        </select>
                                        <small v-if="getQty('menu', l.id, l.qty) !== l.qty"
                                            class="muted">(ajusté)</small>
                                    </span>
                                    <span>{{ formatPrice(l.unit * getQty('menu', l.id, l.qty)) }}</span>
                                </div>
                            </div>

                            <div v-if="selectedItems.room" class="summary-section">
                                <h4><i class="fas fa-bed"></i> Hébergement</h4>
                                <div v-for="l in pricing.lines.filter(x => x.type === 'room')" :key="l.id"
                                    class="summary-item">
                                    <span>{{ l.name }} (× {{ l.qty }})</span>
                                    <span>{{ formatPrice(l.lineTotal) }}</span>
                                </div>
                            </div>

                            <div class="summary-section">
                                <h4><i class="fas fa-calendar"></i> Détails du séjour</h4>
                                <div class="summary-item">
                                    <span>Du {{ formatDate(selectedDates.start) }} au {{
                                        formatDate(displayEndInclusive(selectedDates.endExclusive)) }}
                                    </span>
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
                                <input type="text" v-model="contactInfo.name" placeholder="Prenom" class="form-input" />
                                <input type="text" v-model="contactInfo.last_name" placeholder="Nom"
                                    class="form-input" />
                            </div>
                            <div class="form-row">
                                <input type="email" v-model="contactInfo.email" placeholder="Email"
                                    class="form-input" />
                                <input type="tel" v-model="contactInfo.phone" placeholder="Téléphone"
                                    class="form-input" />
                            </div>
                            <textarea v-model="contactInfo.message" placeholder="Message (optionnel)"
                                class="form-textarea"></textarea>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="modal-footer">
                    <div class="footer-left">
                        <button v-if="currentStep > 1" @click="previousStep" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Précédent
                        </button>
                    </div>
                    <div class="footer-right">
                        <button v-if="currentStep < 5" @click="nextStep" class="btn btn-primary btn-sm"
                            :disabled="!canProceed">
                            Suivant <i class="fas fa-arrow-right"></i>
                        </button>

                        <!-- NOUVELLE SECTION: 3 actions pour l'étape 5 -->
                        <div v-else class="quote-final-step">
                            <!-- Explication des options -->
                            <div class="actions-explanation">
                                <p class="explanation-text">
                                    <i class="fas fa-info-circle"></i>
                                    Choisissez comment finaliser votre demande :
                                </p>
                            </div>

                            <div class="quote-actions">
                                <!-- Action 1: Réserver et payer -->
                                <button @click="createReservationAndPay" class="btn btn-success btn-sm"
                                    :disabled="isSubmitting || !canSubmit">
                                    <i v-if="isSubmitting === 'booking'" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-credit-card"></i>
                                    {{ isSubmitting === 'booking' ? 'Traitement...' : 'Réserver & Payer' }}
                                </button>

                                <!-- Action 2: Sauvegarder et voir contacts -->
                                <button @click="saveQuoteAndShowContacts" class="btn btn-primary btn-sm"
                                    :disabled="isSubmitting || !canSubmit">
                                    <i v-if="isSubmitting === 'saving'" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-bookmark"></i>
                                    {{ isSubmitting === 'saving' ? 'Sauvegarde...' : 'Sauvegarder le devis' }}
                                </button>

                                <!-- Action 3: Conseil personnalisé -->
                                <button @click="requestAdvice" class="btn btn-outline btn-sm"
                                    :disabled="isSubmitting || !canSubmit">
                                    <i v-if="isSubmitting === 'advice'" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-user-tie"></i>
                                    {{ isSubmitting === 'advice' ? 'Envoi...' : 'Conseil personnalisé' }}
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import { nextTick } from 'vue'
import { publicApi } from '@/services/PublicApi'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import frLocale from '@fullcalendar/core/locales/fr'
import { computeQuoteTotal } from '@/shared/composables/useQuotePricing'

export default {
  name: 'QuoteModal',
  components: { FullCalendar },

  props: { show: { type: Boolean, default: false } },

  data() {
    return {
      currentStep: 1,
      loading: false,
      isSubmitting: false,

      // Quantités ajustées au récap
      qtyOverrides: { activity: {}, menu: {} },

      // Catalogues (on charge tout d’un coup)
      allProducts: [],

      // Sélections
      selectedItems: { activities: [], room: null, menus: [] },
      selectedDates: { start: '', endExclusive: '', guests: 2 },
      contactInfo: { name: '', last_name: '', email: '', phone: '', message: '' },
    }
  },

  computed: {
    // --- Filtres produits ---
    relevantProducts() {
      return this.allProducts.filter(p => {
        const label = p.typeConfig?.label
        return ['Activités', 'Menus', 'Hébergements', 'Rooms'].includes(label)
      })
    },
    availableActivities() {
      return this.relevantProducts.filter(p => p.typeConfig?.label === 'Activités')
    },
    availableMenus() {
      return this.relevantProducts.filter(p => p.typeConfig?.label === 'Menus')
    },
    availableRooms() {
      return this.relevantProducts
        .filter(p => p.typeConfig?.label === 'Hébergements' || p.typeConfig?.label === 'Rooms')
        .filter(room => {
          const capacity = room.productable_data?.capacity || room.productableData?.capacity
          return !capacity || capacity >= this.selectedDates.guests
        })
    },

    calendarApi() { return this.$refs.fc?.getApi?.() },

    stepHelpText() {
      switch (this.currentStep) {
        case 1:
          return this.datesOK && this.selectedDates.guests >= 1
            ? 'Parfait ! Continuez vers les activités.'
            : 'Sélectionnez vos dates et le nombre de personnes pour continuer.'
        case 2: return 'Les activités sont optionnelles, vous pouvez passer à l’étape suivante.'
        case 3: return 'Les menus sont optionnels, vous pouvez passer à l’étape suivante.'
        case 4: return this.selectedItems.room
          ? 'Parfait ! Votre hébergement est sélectionné.'
          : 'Veuillez choisir un hébergement pour continuer.'
        case 5: return this.canSubmit
          ? 'Vérifiez vos informations et choisissez votre action.'
          : 'Complétez vos coordonnées pour finaliser.'
        default: return ''
      }
    },

    minDate() {
      const d = new Date()
      d.setDate(d.getDate() + 1)
      return d.toISOString().split('T')[0]
    },

    // --- Pricing centralisé ---
    pricing() {
      const selected = {
        activity: this.selectedItems.activities.map(a => a.id),
        menu: this.selectedItems.menus.map(m => m.id),
        room: this.selectedItems.room?.id ? [this.selectedItems.room.id] : []
      }
      const catalog = {
        activities: this.availableActivities,
        menus: this.availableMenus,
        rooms: this.availableRooms
      }
      const dates = {
        checkin: this.selectedDates.start,
        // FullCalendar fournit end EXCLUSIF → on convertit en checkout inclusif
        checkout: this.displayEndInclusive(this.selectedDates.endExclusive),
        guests: this.selectedDates.guests
      }
      return computeQuoteTotal({
        selected, catalog, dates,
        rules: {
          roomPerNight: true,
          // activités & menus par personne (pas par nuit)
          activityPerGuest: true,
          activityPerGuestPerNight: false,
          menuPerGuest: true,
          menuPerGuestPerNight: false,
          capQtyToGuests: true
        },
        overrides: this.qtyOverrides
      })
    },

    totalPrice() { return this.pricing.total },
    nights()     { return this.pricing.nights },

    datesOK() {
      // nuit(s) > 0 suffit puisque pricing.nights est la source de vérité
      return this.nights > 0
    },

    canProceed() {
      switch (this.currentStep) {
        case 1: return this.datesOK && this.selectedDates.guests >= 1
        case 2: return true
        case 3: return true
        case 4: return !!this.selectedItems.room
        default: return true
      }
    },

    canSubmit() {
      const c = this.contactInfo
      return !!c.name && !!c.email && !!c.phone && this.totalPrice > 0
    },

    fcOptions() {
      return {
        plugins: [dayGridPlugin, interactionPlugin],
        locales: [frLocale],
        locale: 'fr',
        initialView: 'dayGridMonth',
        firstDay: 1,
        height: 'auto',
        contentHeight: 260,
        aspectRatio: 1.6,
        handleWindowResize: false,
        dayHeaderFormat: { weekday: 'short' },
        titleFormat: { year: 'numeric', month: 'long' },
        selectable: true,
        selectMirror: true,
        fixedWeekCount: false,
        validRange: { start: this.minDate },
        headerToolbar: { left: 'prev,next today', center: 'title', right: '' },
        select: this.fcOnSelect,
      }
    }
  },

  watch: {
    show(val) { if (val) this.initializeModal() },
    // clamp des overrides si le nb d’invités baisse
    'selectedDates.guests'(g) {
      const max = Math.max(1, Number(g || 1))
      for (const t of ['activity', 'menu']) {
        for (const id in this.qtyOverrides[t]) {
          const v = Number(this.qtyOverrides[t][id] || 0)
          if (v > max) this.qtyOverrides[t][id] = max
        }
      }
    }
  },

  methods: {
    // --- Init & chargement ---
    async initializeModal() {
      this.currentStep = 1
      this.resetSelections()
      await this.loadAllProducts()
      await nextTick()
    },

    async loadAllProducts() {
      this.loading = true
      try {
        const response = await publicApi.getProducts({ status: 'active', per_page: 100 })
        this.allProducts = response?.data || []
        console.log('✅ Produits chargés:', {
          total: this.allProducts.length,
          activities: this.availableActivities.length,
          menus: this.availableMenus.length,
          rooms: this.availableRooms.length,
          sample_product: (response?.data || [])[0]
        })
      } catch (error) {
        console.error('❌ Erreur chargement produits:', error)
        this.allProducts = []
      } finally {
        this.loading = false
      }
    },

    // --- Sélections ---
    toggleActivity(activity) {
      const i = this.selectedItems.activities.findIndex(a => a.id === activity.id)
      if (i > -1) this.selectedItems.activities.splice(i, 1)
      else this.selectedItems.activities.push(activity)
      // reset override par défaut = autoset (composable)
      delete this.qtyOverrides.activity[activity.id]
    },

    toggleMenu(menu) {
      const i = this.selectedItems.menus.findIndex(m => m.id === menu.id)
      if (i > -1) this.selectedItems.menus.splice(i, 1)
      else this.selectedItems.menus.push(menu)
      delete this.qtyOverrides.menu[menu.id]
    },

    selectRoom(room) {
      this.selectedItems.room = room
    },

    // --- Quantités (réglage manuel au récap) ---
    getLineMaxQty() {
      // par défaut : limité au nombre d’invités
      return Math.max(1, Number(this.selectedDates.guests || 1))
    },
    getQty(type, id, fallback) {
      const m = this.qtyOverrides?.[type] || {}
      const v = m[id]
      return Number.isFinite(Number(v)) ? Number(v) : (fallback ?? 1)
    },
    setQty(type, id, value) {
      const v = Math.max(0, Math.floor(Number(value || 0)))
      if (!this.qtyOverrides[type]) this.qtyOverrides[type] = {}
      this.qtyOverrides[type][id] = v
    },

    // --- Navigation ---
    increaseGuests() { if (this.selectedDates.guests < 20) this.selectedDates.guests++ },
    decreaseGuests() { if (this.selectedDates.guests > 1)  this.selectedDates.guests-- },
    nextStep()       { if (this.canProceed && this.currentStep < 5) this.currentStep++ },
    previousStep()   { if (this.currentStep > 1) this.currentStep-- },
    closeModal()     { this.$emit('close') },

    resetSelections() {
      this.selectedItems = { activities: [], room: null, menus: [] }
      this.selectedDates = { start: '', endExclusive: '', guests: 2 }
      this.contactInfo  = { name: '', last_name: '', email: '', phone: '', message: '' }
      this.qtyOverrides = { activity: {}, menu: {} }
    },

    // --- Dates utils ---
    toUTCDateParts(yyyyMmDd) {
      const [y, m, d] = (yyyyMmDd || '').split('-').map(Number)
      return isNaN(y) ? null : Date.UTC(y, (m || 1) - 1, d || 1)
    },
    displayEndInclusive(endExclusiveYmd) {
      const e = this.toUTCDateParts(endExclusiveYmd)
      if (e == null) return ''
      const d = new Date(e - 24 * 60 * 60 * 1000)
      const yyyy = d.getUTCFullYear()
      const mm   = String(d.getUTCMonth() + 1).padStart(2, '0')
      const dd   = String(d.getUTCDate()).padStart(2, '0')
      return `${yyyy}-${mm}-${dd}`
    },

    // --- Format utils ---
    formatPrice(n) {
      return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(Number(n || 0))
    },
    formatDate(s) {
      return s ? new Date(s).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) : ''
    },

    // --- FullCalendar ---
    fcOnSelect(info) {
      // start inclusif / end exclusif
      this.selectedDates.start       = info.startStr
      this.selectedDates.endExclusive = info.endStr
    },

    // --- Actions finales ---
    async createReservationAndPay() {
      if (!this.canSubmit || this.isSubmitting) return
      this.isSubmitting = 'booking'
      try {
        if (!this.selectedItems.room) throw new Error('Veuillez sélectionner un hébergement avant de procéder au paiement.')
        const quoteResponse = await this.saveQuote()
        if (!quoteResponse.success) throw new Error(quoteResponse.message || 'Impossible de créer le devis')
        const quote = quoteResponse.quote_request

        if (quote.status === 'draft') {
          this.showEmailValidationRequired(quote)
          return
        }

        const paymentResponse = await this.createStripeSession(quote.id)
        if (!paymentResponse.success) throw new Error(paymentResponse.error || 'Impossible de créer la session de paiement')
        window.location.href = paymentResponse.checkout_url
      } catch (e) {
        console.error('❌ Erreur paiement:', e)
        alert('❌ ' + (e.message || 'Erreur inconnue'))
      } finally {
        this.isSubmitting = false
      }
    },

    showEmailValidationRequired(quote) {
      this.closeModal()
      alert(`📧 Validation email requise

Votre devis ${quote.quote_reference} a été créé !

1) Vérifiez votre boîte (${quote.email || this.contactInfo.email})
2) Cliquez sur le lien de validation
3) Reprenez le paiement ici`)
    },

    async createStripeSession(quoteId) {
      const response = await fetch(`${import.meta.env.VITE_API_URL}/stripe/create-payment-session`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ quote_id: quoteId })
      })
      const data = await response.json()
      if (!response.ok) throw new Error(data.error || 'Erreur lors de la création de la session de paiement')
      return data
    },

    async saveQuoteAndShowContacts() {
      if (!this.canSubmit || this.isSubmitting) return
      this.isSubmitting = 'saving'
      try {
        if (!this.selectedItems.room) throw new Error('Veuillez sélectionner un hébergement avant de sauvegarder le devis.')
        const result = await this.saveQuote()
        if (!result.success) throw new Error(result.message || 'Erreur lors de la sauvegarde')
        this.showSuccessModal({
          title: '📧 Email de validation envoyé !',
          message: `Votre devis ${result.quote_request.quote_reference} a été créé.
Vérifiez votre boîte email et validez-le (lien valable 48h).`,
          showContacts: true
        })
        this.$emit('quote-saved', { quote: result.quote_request, type: 'email_validation_required' })
      } catch (e) {
        console.error('❌ Erreur sauvegarde:', e)
        alert('❌ ' + (e.message || 'Erreur inconnue'))
      } finally {
        this.isSubmitting = false
      }
    },

    async requestAdvice() {
      if (!this.canSubmit || this.isSubmitting) return
      this.isSubmitting = 'advice'
      try {
        const adviceData = {
          type: 'advice_request',
          email: this.contactInfo.email,
          contact: this.contactInfo,
          dates: this.selectedDates,
          selected_products: this.getSelectedProducts(),
          total_price: this.totalPrice,
          message: this.contactInfo.message || 'Demande de conseil personnalisé'
        }
        const response = await publicApi.requestAdvice(adviceData)
        if (!response.success) throw new Error(response.message || 'Erreur lors de l’envoi')
        this.showSuccessModal({
          title: '👨‍💼 Demande de conseil envoyée !',
          message: `Merci ${this.contactInfo.name}, un expert vous recontacte très vite.`,
          showContacts: true
        })
      } catch (e) {
        console.error('❌ Erreur conseil:', e)
        alert('❌ ' + (e.message || 'Erreur inconnue'))
      } finally {
        this.isSubmitting = false
      }
    },

    // --- Sauvegarde (aligne les quantités du récap) ---
    async saveQuote() {
      const items = (this.pricing?.lines || [])
        .map(l => ({ product_id: l.id, quantity: Math.max(0, Math.floor(Number(l.qty || 0))) }))
        .filter(it => it.quantity > 0)

      if (!this.selectedItems.room) throw new Error('Veuillez sélectionner un hébergement.')
      const product_ids = []
      for (const it of items) for (let i = 0; i < it.quantity; i++) product_ids.push(it.product_id)
      if (!product_ids.length) throw new Error('Aucun produit sélectionné.')

      const quoteData = {
        email: this.contactInfo.email,
        contact: {
          name: this.contactInfo.name,
          last_name: this.contactInfo.last_name,
          phone: this.contactInfo.phone,
          message: this.contactInfo.message
        },
        dates: {
          checkin: this.selectedDates.start,
          endExclusive: this.selectedDates.endExclusive,
          guests: this.selectedDates.guests
        },
        total_price: this.totalPrice,
        product_ids,
        // items,                  // décommente si le backend accepte
        // include_line_items: true
      }

      console.log('📤 Données envoyées à l’API:', { ...quoteData, product_ids_count: product_ids.length })
      return await publicApi.saveQuote(quoteData)
    },

    // --- Divers / legacy helpers ---
    getSelectedProductIds() {
      const ids = []
      this.selectedItems.activities.forEach(a => ids.push(a.id))
      if (this.selectedItems.room) ids.push(this.selectedItems.room.id)
      this.selectedItems.menus.forEach(m => ids.push(m.id))
      return ids
    },
    getSelectedProducts() {
      return { activities: this.selectedItems.activities, room: this.selectedItems.room, menus: this.selectedItems.menus }
    },
    showSuccessModal({ title, message }) {
      this.closeModal()
      alert(`${title}\n\n${message}`)
    }
  }
}
</script>

<style lang="scss" scoped>
/* Styles identiques à l'original - pas de changement */
$primary: #5e72e4;
$success: #2dce89;
$warning: #fb6340;
$danger: #f5365c;
$terracotta: #c17c4a;

/* ===== MODAL BASE ===== */
.modal-content {
    display: block !important;
    padding: 1rem 1.25rem;
}

.quote-modal .modal-body {
    max-height: 70vh;
    overflow: auto;
}

.quote-modal {
    max-width: 900px;
    width: 95%;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* ===== ÉTAPES PROGRESS ===== */
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
    color: #fff;
}

.progress-steps .step.completed .step-number {
    background: #10b981;
    color: #fff;
}

/* ===== CONTENU STEPS ===== */
.step-content {
    width: 100% !important;
    max-width: none !important;
    margin: 0 auto !important;
    border: 0 !important;
    box-shadow: none !important;
    background: transparent;
}

/* ===== GRILLE HARMONISÉE ===== */
.mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

@media (max-width: 640px) {
    .mini-grid {
        grid-template-columns: 1fr;
    }
}

/* ===== CARTES HARMONISÉES ===== */
.mini-card {
    position: relative;
    display: grid;
    grid-template-columns: 96px 1fr;
    gap: 10px;
    padding: 10px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
}

.mini-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .15);
}

.mini-card.selected {
    border-color: $terracotta;
    background: rgba(193, 124, 74, 0.05);
}

.mini-thumb {
    width: 96px;
    height: 96px;
    border-radius: 10px;
    object-fit: cover;
    display: block;
}

.step-help {
    background: rgba(94, 114, 228, 0.1);
    border-left: 4px solid #5e72e4;
    padding: 0.75rem 1rem;
    margin: 1rem 0;
    border-radius: 4px;
    font-size: 0.9rem;
    color: #5e72e4;

    i {
        margin-right: 0.5rem;
    }

    &.help-warning {
        background: rgba(251, 99, 64, 0.1);
        border-left-color: #fb6340;
        color: #fb6340;
    }
}

.mini-info {
    display: grid;
    gap: 6px;
    align-content: start;
}

.mini-title {
    margin: 0;
    font-size: .95rem;
    line-height: 1.2;
    color: #333;
    font-weight: 600;
}

.mini-meta {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.mini-price {
    font-weight: 700;
    font-size: .9rem;
    color: $terracotta;
}

.mini-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: .75rem;
    opacity: .8;
    padding: 2px 6px;
    border-radius: 999px;
    background: rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.mini-desc {
    margin: 0;
    font-size: .8rem;
    opacity: .8;
    color: #666;
    line-height: 1.3;
}

.mini-check {
    position: absolute;
    right: 8px;
    bottom: 8px;
    background: rgba(0, 0, 0, .6);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.mini-card.selected .mini-check {
    background: $terracotta;
}

/* ===== CALENDRIER ===== */
.dates-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem 1.5rem;
    align-items: start;
}

/* ===== CALENDRIER COMPACT ===== */
.calendar--compact :deep(.fc) {
    width: 100%;
    max-width: 520px;
    /* ajuste 480–600 si tu veux */
    margin: 0 auto 1rem;
    /* centre le bloc */
}

.calendar--compact :deep(.fc-daygrid-day-number) {
    padding: 4px 6px;
    font-size: 0.85rem;
}

.calendar--compact :deep(.fc-daygrid-day-frame) {
    min-height: 30px; // cellules moins hautes
}

/* Header + nav boutons */
.calendar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .5rem;
}

.calendar-nav {
    display: inline-flex;
    gap: .25rem;
}

.nav-btn {
    border: 1px solid #ddd;
    background: #fff;
    border-radius: 8px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.nav-btn:hover {
    background: #f8f8f8;
}

.nav-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

/* accessibilité */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}

.range-info,
.duration {
    margin-top: .5rem;
    display: flex;
    align-items: center;
    gap: .5rem;
    color: $terracotta;
    font-weight: 500;
}

/* ===== SECTION RÉCAP FINALE ===== */
.quote-final-step {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1rem;
}

.actions-explanation {
    text-align: center;
    margin-bottom: 0.5rem;
    padding: 0.75rem;
    background: rgba(193, 124, 74, 0.1);
    border-radius: 8px;
    border-left: 4px solid $terracotta;

    .explanation-text {
        color: #666;
        font-size: 0.9rem;
        margin: 0;

        i {
            color: $terracotta;
            margin-right: 0.5rem;
        }
    }
}

.quote-actions {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;

    .btn {
        flex: 1;
        min-width: 140px;
        white-space: nowrap;
        font-size: 0.9rem;

        &.btn-success {
            background: $success;
            border-color: $success;

            &:hover {
                background: darken($success, 10%);
            }
        }

        &.btn-primary {
            background: $primary;
            border-color: $primary;

            &:hover {
                background: darken($primary, 10%);
            }
        }

        &.btn-outline {
            background: transparent;
            border: 2px solid $terracotta;
            color: $terracotta;

            &:hover {
                background: $terracotta;
                color: white;
            }
        }

        @media (max-width: 768px) {
            min-width: 120px;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
    }

    @media (max-width: 640px) {
        flex-direction: column;

        .btn {
            flex: none;
            width: 100%;
            min-width: auto;
        }
    }
}

/* ===== LOADING STATE ===== */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 2rem;
    color: #666;
}

.spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid $terracotta;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

/* ===== RÉCAP SUMMARY ===== */
.quote-summary {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.summary-section {
    margin-bottom: 1.5rem;

    &:last-child {
        margin-bottom: 0;
    }

    h4 {
        color: $terracotta;
        font-size: 1.1rem;
        margin-bottom: 0.75rem;
        font-weight: 600;

        i {
            margin-right: 0.5rem;
        }
    }
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);

    &:last-child {
        border-bottom: none;
    }

    span:first-child {
        color: #333;
        font-weight: 500;
    }

    span:last-child {
        color: $terracotta;
        font-weight: 600;
    }
}

.summary-total {
    border-top: 2px solid $terracotta;
    padding-top: 1rem;
    margin-top: 1rem;
}

.total-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.2rem;

    span {
        color: #333;
    }

    strong {
        color: $terracotta;
        font-size: 1.4rem;
    }
}

/* ===== FORMULAIRE CONTACT ===== */
.contact-form {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.1);

    h4 {
        color: $terracotta;
        margin-bottom: 1rem;
        font-weight: 600;
    }
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;

    @media (max-width: 640px) {
        grid-template-columns: 1fr;
    }
}

.form-input,
.form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-bottom: 1rem;

    &:focus {
        outline: none;
        border-color: $terracotta;
        box-shadow: 0 0 0 2px rgba(193, 124, 74, 0.1);
    }
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

.qty-select {
    margin-left: .5rem;
    padding: .25rem .5rem;
    font-size: .85rem;
}

.muted {
    color: #777;
    margin-left: .25rem;
}
</style>