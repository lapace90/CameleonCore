<template>
  <teleport to="body">
    <div v-if="show" class="email-modal-overlay" @click="closeModal">
      <div class="email-modal-box" @click.stop>
        <div class="email-modal-icon">
          <i class="fas fa-envelope-circle-check"></i>
        </div>
        
        <h2><i class="fas fa-envelope"></i> Validation email requise</h2>
        
        <p>Votre devis <strong>{{ quoteReference }}</strong> a été créé !</p>
        
        <div class="email-modal-steps">
          <div class="email-modal-step">
            <span class="step-num">1</span>
            <span>Vérifiez votre boîte mail <strong>{{ email }}</strong></span>
          </div>
          <div class="email-modal-step">
            <span class="step-num">2</span>
            <span>Cliquez sur le lien de validation</span>
          </div>
          <div class="email-modal-step">
            <span class="step-num">3</span>
            <span>Reprenez le paiement depuis l'email</span>
          </div>
        </div>
        
        <div class="email-modal-info">
          <i class="fas fa-info-circle"></i>
          <span>Le lien est valable <strong>48h</strong>. Vérifiez vos spams !</span>
        </div>
        
        <button @click="closeModal" class="email-modal-btn">
          <i class="fas fa-check"></i> J'ai compris
        </button>
      </div>
    </div>
  </teleport>
</template>

<script>
export default {
  name: 'EmailValidationModal',
  props: {
    show: { type: Boolean, default: false },
    quoteReference: { type: String, default: '' },
    email: { type: String, default: '' }
  },
  emits: ['close'],
  methods: {
    closeModal() {
      this.$emit('close')
    }
  }
}
</script>

<style>
/* NON SCOPED - nécessaire avec teleport */
.email-modal-overlay {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  right: 0 !important;
  bottom: 0 !important;
  background: rgba(0, 0, 0, 0.6) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  z-index: 9998 !important;
  padding: 1rem !important;
}

.email-modal-box {
  background: white !important;
  border-radius: 16px !important;
  max-width: 480px !important;
  width: 100% !important;
  padding: 2rem !important;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
  text-align: center !important;
}

.email-modal-icon i {
  font-size: 3.5rem;
  color: #10b981;
}

.email-modal-box h2 {
  color: #41241C;
  font-size: 1.4rem;
  margin: 1rem 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.email-modal-box h2 i { color: #CE5E1A; }
.email-modal-box > p { color: #4b5563; margin-bottom: 1.5rem; }
.email-modal-box strong { color: #CE5E1A; }

.email-modal-steps { text-align: left; margin-bottom: 1.5rem; }

.email-modal-step {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #CE5E1A;
  margin-bottom: 0.5rem;
}

.step-num {
  width: 28px;
  height: 28px;
  background: #CE5E1A;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.85rem;
  flex-shrink: 0;
}

.email-modal-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #dbeafe;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
  margin-bottom: 1.5rem;
  text-align: left;
  font-size: 0.9rem;
  color: #1e40af;
}

.email-modal-info i { color: #3b82f6; flex-shrink: 0; }

.email-modal-btn {
  background: #CE5E1A;
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.email-modal-btn:hover { background: #a84b15; }
</style>