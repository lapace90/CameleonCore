<template>
  <div class="contact-page">
    <!-- Hero Section -->
    <section class="contact-hero">
      <div class="container">
        <div class="hero-content">
          <h1>Contactez-nous</h1>
          <p class="hero-subtitle">
            Une question ? Un projet ? Notre équipe est là pour vous accompagner.
          </p>
        </div>
      </div>
    </section>

    <!-- Contact Form Section -->
    <section class="contact-form-section">
      <div class="container">
        <div class="contact-grid">
          <!-- Contact Form -->
          <div class="form-container">
            <div class="form-header">
              <h2>Envoyez-nous un message</h2>
              <p>Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.</p>
            </div>
            
            <form @submit.prevent="submitForm" class="contact-form">
              <div class="form-row">
                <div class="form-group">
                  <label for="firstName">Prénom *</label>
                  <input 
                    type="text" 
                    id="firstName" 
                    v-model="form.firstName"
                    :class="{ 'error': errors.firstName }"
                    required
                  >
                  <span v-if="errors.firstName" class="error-message">{{ errors.firstName }}</span>
                </div>
                
                <div class="form-group">
                  <label for="lastName">Nom *</label>
                  <input 
                    type="text" 
                    id="lastName" 
                    v-model="form.lastName"
                    :class="{ 'error': errors.lastName }"
                    required
                  >
                  <span v-if="errors.lastName" class="error-message">{{ errors.lastName }}</span>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-group">
                  <label for="email">Email *</label>
                  <input 
                    type="email" 
                    id="email" 
                    v-model="form.email"
                    :class="{ 'error': errors.email }"
                    required
                  >
                  <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
                </div>
                
                <div class="form-group">
                  <label for="phone">Téléphone</label>
                  <input 
                    type="tel" 
                    id="phone" 
                    v-model="form.phone"
                  >
                </div>
              </div>
              
              <div class="form-group">
                <label for="subject">Sujet *</label>
                <select 
                  id="subject" 
                  v-model="form.subject"
                  :class="{ 'error': errors.subject }"
                  required
                >
                  <option value="">Choisissez un sujet</option>
                  <option value="reservation">Réservation</option>
                  <option value="information">Demande d'information</option>
                  <option value="complaint">Réclamation</option>
                  <option value="suggestion">Suggestion</option>
                  <option value="other">Autre</option>
                </select>
                <span v-if="errors.subject" class="error-message">{{ errors.subject }}</span>
              </div>
              
              <div class="form-group">
                <label for="message">Message *</label>
                <textarea 
                  id="message" 
                  v-model="form.message"
                  :class="{ 'error': errors.message }"
                  rows="6"
                  placeholder="Décrivez votre demande en détail..."
                  required
                ></textarea>
                <span v-if="errors.message" class="error-message">{{ errors.message }}</span>
              </div>
              
              <div class="form-group checkbox-group">
                <label class="checkbox-label">
                  <input 
                    type="checkbox" 
                    v-model="form.newsletter"
                  >
                  <span class="checkmark"></span>
                  Je souhaite recevoir la newsletter avec les offres et actualités
                </label>
              </div>
              
              <div class="form-group checkbox-group">
                <label class="checkbox-label">
                  <input 
                    type="checkbox" 
                    v-model="form.privacy"
                    :class="{ 'error': errors.privacy }"
                    required
                  >
                  <span class="checkmark"></span>
                  J'accepte la politique de confidentialité *
                </label>
                <span v-if="errors.privacy" class="error-message">{{ errors.privacy }}</span>
              </div>
              
              <button type="submit" class="btn btn-primary btn-lg" :disabled="submitting">
                <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
                <i v-else class="fas fa-paper-plane"></i>
                {{ submitting ? 'Envoi en cours...' : 'Envoyer le message' }}
              </button>
            </form>
            
            <!-- Success Message -->
            <div v-if="showSuccess" class="success-message">
              <div class="success-content">
                <i class="fas fa-check-circle"></i>
                <h3>Message envoyé avec succès !</h3>
                <p>Nous avons bien reçu votre message et vous répondrons dans les plus brefs délais.</p>
              </div>
            </div>
          </div>
          
          <!-- Contact Info -->
          <div class="contact-info">
            <div class="info-card">
              <h3>Informations de contact</h3>
              
              <div class="contact-item">
                <div class="contact-icon">
                  <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="contact-details">
                  <h4>Adresse</h4>
                  <p>123 Rue de la Nature<br>75000 Paris, France</p>
                </div>
              </div>
              
              <div class="contact-item">
                <div class="contact-icon">
                  <i class="fas fa-phone"></i>
                </div>
                <div class="contact-details">
                  <h4>Téléphone</h4>
                  <p>+33 1 23 45 67 89</p>
                  <small>Lun - Ven: 9h - 18h<br>Sam: 9h - 16h</small>
                </div>
              </div>
              
              <div class="contact-item">
                <div class="contact-icon">
                  <i class="fas fa-envelope"></i>
                </div>
                <div class="contact-details">
                  <h4>Email</h4>
                  <p>contact@campcameleonx.com</p>
                  <small>Réponse sous 24h</small>
                </div>
              </div>
              
              <div class="contact-item">
                <div class="contact-icon">
                  <i class="fas fa-clock"></i>
                </div>
                <div class="contact-details">
                  <h4>Horaires d'ouverture</h4>
                  <p>
                    <strong>Accueil :</strong><br>
                    8h - 20h (Avril à Septembre)<br>
                    9h - 18h (Octobre à Mars)
                  </p>
                </div>
              </div>
            </div>
            
            <!-- Emergency Contact -->
            <div class="emergency-card">
              <div class="emergency-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h4>Contact d'urgence</h4>
              </div>
              <p>En cas d'urgence pendant votre séjour :</p>
              <a href="tel:+33123456789" class="emergency-phone">
                <i class="fas fa-phone"></i>
                +33 1 23 45 67 89
              </a>
              <small>Disponible 24h/24</small>
            </div>
            
            <!-- Social Media -->
            <div class="social-card">
              <h4>Suivez-nous</h4>
              <div class="social-links">
                <a href="#" class="social-link facebook">
                  <i class="fab fa-facebook-f"></i>
                  <span>Facebook</span>
                </a>
                <a href="#" class="social-link instagram">
                  <i class="fab fa-instagram"></i>
                  <span>Instagram</span>
                </a>
                <a href="#" class="social-link youtube">
                  <i class="fab fa-youtube"></i>
                  <span>YouTube</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
      <div class="container">
        <div class="section-header">
          <h2>Comment nous trouver</h2>
          <p>Situé au cœur de la nature, facilement accessible</p>
        </div>
        
        <div class="map-container">
          <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <h3>Carte interactive</h3>
            <p>Intégration Google Maps à venir</p>
            <div class="map-directions">
              <h4>Accès :</h4>
              <ul>
                <li>Autoroute A6, sortie 18</li>
                <li>Suivre direction "Nature & Camping"</li>
                <li>5km après le village de Beaumont</li>
                <li>Parking gratuit sur place</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: 'PublicContact',
  data() {
    return {
      form: {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        newsletter: false,
        privacy: false
      },
      errors: {},
      submitting: false,
      showSuccess: false
    }
  },
  methods: {
    validateForm() {
      this.errors = {};
      
      if (!this.form.firstName.trim()) {
        this.errors.firstName = 'Le prénom est requis';
      }
      
      if (!this.form.lastName.trim()) {
        this.errors.lastName = 'Le nom est requis';
      }
      
      if (!this.form.email.trim()) {
        this.errors.email = 'L\'email est requis';
      } else if (!this.isValidEmail(this.form.email)) {
        this.errors.email = 'Format d\'email invalide';
      }
      
      if (!this.form.subject) {
        this.errors.subject = 'Veuillez choisir un sujet';
      }
      
      if (!this.form.message.trim()) {
        this.errors.message = 'Le message est requis';
      } else if (this.form.message.trim().length < 10) {
        this.errors.message = 'Le message doit contenir au moins 10 caractères';
      }
      
      if (!this.form.privacy) {
        this.errors.privacy = 'Vous devez accepter la politique de confidentialité';
      }
      
      return Object.keys(this.errors).length === 0;
    },
    
    isValidEmail(email) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return emailRegex.test(email);
    },
    
    async submitForm() {
      if (!this.validateForm()) {
        return;
      }
      
      this.submitting = true;
      
      try {
        // Simulation d'envoi du formulaire
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        console.log('Formulaire soumis:', this.form);
        
        // Afficher le message de succès
        this.showSuccess = true;
        
        // Reset du formulaire
        this.resetForm();
        
        // Masquer le message de succès après 5 secondes
        setTimeout(() => {
          this.showSuccess = false;
        }, 5000);
        
      } catch (error) {
        console.error('Erreur lors de l\'envoi:', error);
        alert('Une erreur est survenue. Veuillez réessayer.');
      } finally {
        this.submitting = false;
      }
    },
    
    resetForm() {
      this.form = {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
        newsletter: false,
        privacy: false
      };
      this.errors = {};
    }
  }
}
</script>

<!-- <style scoped>
.contact-page {
  padding-top: 70px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Hero Section */
.contact-hero {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4rem 0;
  text-align: center;
}

.hero-content h1 {
  font-size: 3rem;
  margin-bottom: 1rem;
  font-weight: 700;
}

.hero-subtitle {
  font-size: 1.25rem;
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto;
}

/* Contact Form Section */
.contact-form-section {
  padding: 5rem 0;
  background: white;
}

.contact-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 4rem;
  align-items: start;
}

.form-container {
  position: relative;
}

.form-header {
  margin-bottom: 2rem;
}

.form-header h2 {
  color: #32325d;
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.form-header p {
  color: #8898aa;
  font-size: 1.1rem;
}

/* Form Styles */
.contact-form {
  background: #f8f9fe;
  padding: 2rem;
  border-radius: 1rem;
  border: 1px solid #e9ecef;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #32325d;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #dee2e6;
  border-radius: 0.5rem;
  background-color: white;
  font-size: 1rem;
  transition: all 0.15s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #5e72e4;
  box-shadow: 0 0 0 0.2rem rgba(94, 114, 228, 0.25);
}

.form-group input.error,
.form-group select.error,
.form-group textarea.error {
  border-color: #f5365c;
}

.error-message {
  color: #f5365c;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

.checkbox-group {
  margin-bottom: 1rem;
}

.checkbox-label {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  cursor: pointer;
  font-weight: normal;
  line-height: 1.5;
}

.checkbox-label input[type="checkbox"] {
  display: none;
}

.checkmark {
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid #dee2e6;
  border-radius: 0.25rem;
  position: relative;
  flex-shrink: 0;
  margin-top: 0.125rem;
  transition: all 0.15s ease;
}

.checkbox-label input:checked + .checkmark {
  background-color: #5e72e4;
  border-color: #5e72e4;
}

.checkbox-label input:checked + .checkmark::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
}

/* Success Message */
.success-message {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 1rem;
  backdrop-filter: blur(5px);
}

.success-content {
  text-align: center;
  padding: 2rem;
}

.success-content i {
  font-size: 4rem;
  color: #2dce89;
  margin-bottom: 1rem;
}

.success-content h3 {
  color: #32325d;
  margin-bottom: 0.5rem;
}

.success-content p {
  color: #8898aa;
}

/* Contact Info */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-card,
.emergency-card,
.social-card {
  background: white;
  padding: 1.5rem;
  border-radius: 0.75rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
}

.info-card h3,
.social-card h4 {
  color: #32325d;
  margin-bottom: 1.5rem;
  font-size: 1.25rem;
}

.contact-item {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #f8f9fe;
}

.contact-item:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.contact-icon {
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.125rem;
  flex-shrink: 0;
}

.contact-details h4 {
  color: #32325d;
  margin-bottom: 0.5rem;
  font-size: 1rem;
}

.contact-details p {
  color: #8898aa;
  margin: 0 0 0.25rem 0;
  line-height: 1.5;
}

.contact-details small {
  color: #adb5bd;
  font-size: 0.875rem;
}

/* Emergency Card */
.emergency-card {
  background: linear-gradient(135deg, #f5365c 0%, #fb6340 100%);
  color: white;
  text-align: center;
}

.emergency-header {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.emergency-header h4 {
  margin: 0;
  color: white;
}

.emergency-phone {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: white;
  text-decoration: none;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 1rem 0 0.5rem 0;
  padding: 0.75rem 1.5rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 0.5rem;
  transition: all 0.15s ease;
}

.emergency-phone:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}

/* Social Media */
.social-links {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.social-link {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  color: white;
  text-decoration: none;
  border-radius: 0.5rem;
  transition: all 0.15s ease;
}

.social-link.facebook {
  background: #1877f2;
}

.social-link.instagram {
  background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
}

.social-link.youtube {
  background: #ff0000;
}

.social-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* Map Section */
.map-section {
  padding: 5rem 0;
  background-color: #f8f9fe;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: 2.5rem;
  color: #32325d;
  margin-bottom: 1rem;
}

.section-header p {
  color: #8898aa;
  font-size: 1.125rem;
}

.map-container {
  background: white;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
  border: 1px solid #e9ecef;
}

.map-placeholder {
  height: 400px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #8898aa;
  text-align: center;
  padding: 2rem;
}

.map-placeholder i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.map-directions {
  margin-top: 2rem;
  text-align: left;
  max-width: 300px;
}

.map-directions h4 {
  color: #32325d;
  margin-bottom: 1rem;
}

.map-directions ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.map-directions li {
  padding: 0.5rem 0;
  color: #8898aa;
  position: relative;
  padding-left: 1.5rem;
}

.map-directions li::before {
  content: '→';
  position: absolute;
  left: 0;
  color: #5e72e4;
  font-weight: 600;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-content h1 {
    font-size: 2.5rem;
  }
  
  .contact-grid {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .contact-form {
    padding: 1.5rem;
  }
  
  .section-header h2 {
    font-size: 2rem;
  }
}
</style> -->