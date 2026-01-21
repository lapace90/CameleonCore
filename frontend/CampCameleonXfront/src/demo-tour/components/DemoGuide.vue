<template>
  <Teleport to="body">
    <!-- Popup guide contextuel -->
    <transition name="slide-up">
      <div v-if="showGuide && currentMessage" class="demo-popup">
        <div class="demo-popup-header">
          <span class="demo-badge">Mode démo</span>
          <button @click="dismiss" class="close-btn" aria-label="Fermer">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="demo-popup-content">
          <p>{{ currentMessage }}</p>
        </div>
        <div v-if="currentStep >= 1 && currentStep <= 5" class="demo-popup-footer">
          <span class="step-indicator">Étape {{ currentStep }} / 5</span>
        </div>
      </div>
    </transition>

    <!-- Popup Mailhog après validation -->
    <transition name="slide-up">
      <div v-if="showMailhog" class="demo-popup demo-popup-mailhog">
        <div class="demo-popup-header">
          <span class="demo-badge demo-badge-success">✓ Devis envoyé</span>
          <button @click="dismissMailhog" class="close-btn" aria-label="Fermer">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="demo-popup-content">
          <p><strong>Un email de confirmation vous a été envoyé !</strong></p>
          <p class="demo-hint">En mode démo, les emails sont capturés par Mailhog.</p>
          <a :href="mailhogUrl" target="_blank" rel="noopener" class="demo-mailhog-btn">
            <i class="fas fa-envelope-open-text"></i>
            Consulter mes emails
          </a>
        </div>
      </div>
    </transition>

    <!-- Bouton flottant -->
    <transition name="bounce">
      <button 
        v-if="showButton" 
        @click="startDemo" 
        class="demo-float-btn"
      >
        <i class="fas fa-play-circle"></i>
        <span>Tester la démo</span>
      </button>
    </transition>
  </Teleport>
</template>

<script>
export default {
  name: 'DemoGuide',
  
  props: {
    mailhogUrl: {
      type: String,
      default: 'https://mail.campcameleonx.ipace.dev'
    }
  },

  data() {
    return {
      showButton: this.isDemoEnabled(),
      showGuide: false,
      showMailhog: false,
      currentStep: 0
    }
  },

  computed: {
    currentMessage() {
      const messages = {
        1: "Glissez sur le calendrier pour sélectionner vos dates d'arrivée et de départ, puis indiquez le nombre de voyageurs.",
        2: "Parcourez nos activités et cliquez sur celles qui vous intéressent. Cette étape est optionnelle.",
        3: "Découvrez nos menus et sélectionnez vos préférés. Cette étape est optionnelle.",
        4: "Choisissez votre hébergement parmi nos offres. Cette étape est obligatoire.",
        5: "Vérifiez votre sélection, renseignez vos coordonnées et validez votre devis."
      }
      return messages[this.currentStep] || null
    }
  },

  methods: {
    isDemoEnabled() {
      if (import.meta.env.VITE_DEMO_MODE === 'false') return false
      if (typeof window === 'undefined') return false
      const params = new URLSearchParams(window.location.search)
      if (params.get('demo') === 'off') return false
      if (localStorage.getItem('campcameleon-demo-disabled') === 'true') return false
      return true
    },

    startDemo() {
      this.showButton = false
      window.dispatchEvent(new CustomEvent('demo:open-quote-modal'))
    },

    dismiss() {
      this.showGuide = false
      this.showButton = true
    },

    dismissMailhog() {
      this.showMailhog = false
      this.showButton = true
    },

    onStepChange(e) {
      this.currentStep = e.detail.step
      this.showGuide = true
      this.showButton = false
    },

    onModalClose() {
      this.showGuide = false
      this.showMailhog = false
      this.showButton = true
      this.currentStep = 0
    },

    onValidation() {
      this.showGuide = false
      this.showMailhog = true
    }
  },

  mounted() {
    window.addEventListener('demo:step-change', this.onStepChange)
    window.addEventListener('demo:modal-close', this.onModalClose)
    window.addEventListener('demo:validation', this.onValidation)
  },

  beforeUnmount() {
    window.removeEventListener('demo:step-change', this.onStepChange)
    window.removeEventListener('demo:modal-close', this.onModalClose)
    window.removeEventListener('demo:validation', this.onValidation)
  }
}
</script>

<style>
.demo-popup {
  position: fixed;
  top: 20px;
  left: 20px;
  width: 300px;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.25);
  z-index: 10000;
  overflow: hidden;
}
.demo-popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: #f5e6d3;
}
.demo-badge {
  font-size: 0.75rem;
  font-weight: 600;
  color: #c65d3b;
  text-transform: uppercase;
}
.demo-badge-success {
  color: #4a7c59;
}
.demo-popup .close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #6b7280;
  padding: 4px;
}
.demo-popup .close-btn:hover {
  color: #c65d3b;
}
.demo-popup-content {
  padding: 1rem;
}
.demo-popup-content p {
  margin: 0 0 0.5rem;
  font-size: 0.95rem;
  line-height: 1.5;
  color: #1a2332;
}
.demo-popup-content p:last-child {
  margin-bottom: 0;
}
.demo-hint {
  font-size: 0.85rem !important;
  color: #6b7280 !important;
}
.demo-popup-footer {
  padding: 0.5rem 1rem;
  background: rgba(245,230,211,0.5);
  border-top: 1px solid rgba(26,35,50,0.05);
}
.step-indicator {
  font-size: 0.75rem;
  color: #6b7280;
}
.demo-mailhog-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  padding: 0.75rem 1rem;
  margin-top: 0.75rem;
  background: #4a7c59;
  color: #fff;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.9rem;
}
.demo-mailhog-btn:hover {
  background: #3d6a4a;
}
.demo-float-btn {
  position: fixed;
  bottom: 20px;
  right: 20px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: #c65d3b;
  color: #fff;
  border: none;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(198,93,59,0.4);
  z-index: 9999;
}
.demo-float-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(198,93,59,0.5);
}
.slide-up-enter-active, .slide-up-leave-active {
  transition: all 0.3s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
.bounce-enter-active {
  animation: demo-bounce 0.4s;
}
.bounce-leave-active {
  animation: demo-bounce 0.3s reverse;
}
@keyframes demo-bounce {
  0% { transform: scale(0); opacity: 0; }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); opacity: 1; }
}
</style>