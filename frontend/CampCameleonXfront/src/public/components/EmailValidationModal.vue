<template>
  <transition name="modal-fade">
    <div v-if="show" class="modal-overlay" @click="closeModal">
      <div class="modal-container" @click.stop>
        <!-- Icône et titre -->
        <div class="modal-icon">
          <i class="fas fa-envelope-circle-check"></i>
        </div>
        
        <h2 class="modal-title"><i class="fas fa-envelope"  style="padding: .5rem;"></i>Validation email requise</h2>
        
        <div class="modal-content">
          <p class="success-message">
            Votre devis <strong> {{ quoteReference }} </strong> a été créé !
          </p>
          
          <div class="steps">
            <div class="step">
              <div class="step-number">1</div>
              <div class="step-text">
                Vérifiez votre boîte mail <br>
                <strong>{{ email }}</strong>
              </div>
            </div>
            
            <div class="step">
              <div class="step-number">2</div>
              <div class="step-text">
                Cliquez sur le lien de validation
              </div>
            </div>
            
            <div class="step">
              <div class="step-number">3</div>
              <div class="step-text">
                Reprenez le paiement depuis l'email
              </div>
            </div>
          </div>
          
          <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <p>Le lien de validation est valable <strong>48 heures</strong>. 
               Pensez à vérifier vos spams !</p>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="closeModal" class="btn btn-primary">
            <i class="fas fa-check"></i> J'ai compris
          </button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
export default {
  name: 'EmailValidationModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    quoteReference: {
      type: String,
      default: ''
    },
    email: {
      type: String,
      default: ''
    }
  },
  emits: ['close'],
  methods: {
    closeModal() {
      this.$emit('close')
    }
  }
}
</script>

<style scoped>

.validation-modal {
  background: white;
  border-radius: 16px;
  max-width: 500px;
  width: 100%;
  padding: 2rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-icon {
  text-align: center;
  margin-bottom: 1rem;
}

.modal-icon i {
  font-size: 4rem;
  color: #10b981;
  animation: scaleIn 0.5s ease;
}

@keyframes scaleIn {
  from {
    transform: scale(0);
  }
  to {
    transform: scale(1);
  }
}

.modal-title {
  text-align: center;
  color: #1f2937;
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
  font-weight: 600;
}

.modal-content {
  margin-bottom: 1.5rem;
}

.success-message {
  text-align: center;
  font-size: 1rem;
  color: #4b5563;
  margin-bottom: 2rem;
}

.success-message strong {
  color: #c17c4a;
  padding-inline: 0.5rem;
  font-weight: 600;
}

.steps {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.step {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #c17c4a;
}

.step-number {
  width: 32px;
  height: 32px;
  background: #c17c4a;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
}

.step-text {
  flex: 1;
  font-size: 0.9rem;
  color: #374151;
  line-height: 1.4;
}

.step-text strong {
  color: #1f2937;
  word-break: break-word;
}

.info-box {
  display: flex;
  align-items: start;
  gap: 0.75rem;
  padding: 1rem;
  background: #dbeafe;
  border-radius: 8px;
  border-left: 4px solid #3b82f6;
}

.info-box i {
  color: #3b82f6;
  font-size: 1.2rem;
  margin-top: 0.2rem;
  flex-shrink: 0;
}

.info-box p {
  margin: 0;
  font-size: 0.875rem;
  color: #1e40af;
  line-height: 1.5;
}

.modal-footer {
  display: flex;
  justify-content: center;
}

.btn {
  padding: 0.75rem 2rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
}

.btn-primary {
  background: #c17c4a;
  color: white;
}

.btn-primary:hover {
  background: #a66a3e;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(193, 124, 74, 0.3);
}

/* Animations */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
  .validation-modal {
    padding: 1.5rem;
  }
  
  .modal-title {
    font-size: 1.25rem;
  }
  
  .modal-icon i {
    font-size: 3rem;
  }
  
  .step {
    padding: 0.75rem;
  }
  
  .step-text {
    font-size: 0.85rem;
  }
}
</style>