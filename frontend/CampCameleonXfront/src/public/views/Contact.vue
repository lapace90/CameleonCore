<template>
  <div class="contact-page">
    <!-- Hero Section -->
    <section class="contact-hero">
      <div class="container">
        <div class="hero-content">
          <h1>Contactez-nous</h1>
          <p class="hero-subtitle">
            Une question ? Un projet ? Notre équipe est là pour vous accompagner dans votre aventure désert.
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
            <div class="form-header-contact">
              <h2>Envoyez-nous un message</h2>
              <p>Remplissez le formulaire ci-dessous et nous vous répondrons dans les plus brefs délais.</p>
            </div>

            <form @submit.prevent="submitForm" class="contact-form">
              <div class="form-row">
                <div class="form-group">
                  <label for="firstName">Prénom *</label>
                  <input type="text" id="firstName" v-model="form.firstName" :class="{ 'error': errors.firstName }"
                    required>
                  <span v-if="errors.firstName" class="error-message">{{ errors.firstName }}</span>
                </div>

                <div class="form-group">
                  <label for="lastName">Nom *</label>
                  <input type="text" id="lastName" v-model="form.lastName" :class="{ 'error': errors.lastName }"
                    required>
                  <span v-if="errors.lastName" class="error-message">{{ errors.lastName }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label for="email">Email *</label>
                  <input type="email" id="email" v-model="form.email" :class="{ 'error': errors.email }" required>
                  <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
                </div>

                <div class="form-group">
                  <label for="phone">Téléphone</label>
                  <input type="tel" id="phone" v-model="form.phone">
                </div>
              </div>

              <div class="form-group">
                <label for="subject">Sujet *</label>
                <select id="subject" v-model="form.subject" :class="{ 'error': errors.subject }" required>
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
                <textarea id="message" v-model="form.message" :class="{ 'error': errors.message }" rows="6"
                  placeholder="Décrivez votre demande en détail..." required></textarea>
                <span v-if="errors.message" class="error-message">{{ errors.message }}</span>
              </div>

              <div class="form-group checkbox-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="form.newsletter">
                  <span class="checkmark"></span>
                  Je souhaite recevoir la newsletter avec les offres et actualités
                </label>
              </div>

              <div class="form-group checkbox-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="form.privacy" :class="{ 'error': errors.privacy }" required>
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
                  <p>Route du Sahara<br>Merzouga, Maroc</p>
                </div>
              </div>

              <div class="contact-item">
                <div class="contact-icon">
                  <i class="fas fa-phone"></i>
                </div>
                <div class="contact-details">
                  <h4>Téléphone</h4>
                  <p>+212 6 12 34 56 78</p>
                  <small>7j/7 : 8h - 20h<br>Réception 24h/24</small>
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
                  <h4>Saison & Horaires</h4>
                  <p>
                    <strong>Haute saison :</strong><br>
                    Octobre à Mars<br>
                    <strong>Basse saison :</strong><br>
                    Avril à Septembre
                  </p>
                </div>
              </div>
            </div>

            <!-- Emergency Contact -->
            <div class="emergency-card">
              <div class="emergency-header">
                <i class="fas fa-moon"></i>
                <h4>Assistance 24h/24</h4>
              </div>
              <p>En cas d'urgence pendant votre séjour au désert :</p>
              <a href="tel:+212612345678" class="emergency-phone">
                <i class="fas fa-phone"></i>
                +212 6 12 34 56 78
              </a>
              <br>
              <small>Guide d'urgence disponible jour et nuit</small>
            </div>

            <!-- Social Media -->
            <div class="social-card">
              <h4>Suivez nos aventures</h4>
              <div class="social-links">
                <a href="#" class="social-link facebook">
                  <i class="fab fa-facebook-f"></i>
  
                </a>
                <a href="#" class="social-link instagram">
                  <i class="fab fa-instagram"></i>
  
                </a>
                <a href="#" class="social-link youtube">
                  <i class="fab fa-youtube"></i>
  
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
          <h2>Au cœur du Sahara</h2>
          <p>Découvrez notre emplacement unique dans le désert de Merzouga</p>
        </div>

        <div class="map-container">
          <div class="map-placeholder">
            <i class="fas fa-map-marked-alt"></i>
            <h3>Localisation désert</h3>
            <p>Intégration carte interactive à venir</p>
            <div class="map-directions">
              <h4>Comment nous rejoindre :</h4>
              <ul>
                <li>Depuis Erfoud : 55km direction Sud</li>
                <li>Suivre "Merzouga - Erg Chebbi"</li>
                <li>Dunes dorées visibles à l'horizon</li>
                <li>Stationnement sécurisé gratuit</li>
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

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.contact-page {
  margin-top: -120px; // Background remonte sous header transparent
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Hero Section */
.contact-hero {
  background: $gradient-hero;
  color: white;
  padding: 10rem 0 4rem; // Plus d'espace pour le contenu
  text-align: center;
  position: relative;

  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(ellipse at center, transparent 0%, rgba($coffee, 0.2) 100%);
  }

  .hero-content {
    position: relative;
    z-index: 2;
    padding-top: 2rem; // Espace sécuritaire sous header
  }

  h1 {
    font-size: $font-size-6xl;
    margin-bottom: 1.5rem;
    font-family: $font-family-display;
    font-weight: 400;
  }

  .hero-subtitle {
    font-size: $font-size-xl;
    opacity: 0.95;
    max-width: 700px;
    margin: 0 auto;
    line-height: $line-height-relaxed;
    font-family: $font-family-primary;
  }
}

/* Contact Form Section */
.contact-form-section {
  padding: 5rem 0;
  background: $bg-secondary;
}

.contact-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 4rem;

  @media (max-width: 991px) {
    grid-template-columns: 1fr;
    gap: 3rem;
  }
}

.form-container {
  position: relative;
}

.form-header-contact {
  margin-bottom: 2rem;

  h2 {
    color: $text-primary;
    font-size: $font-size-3xl;
    margin-bottom: 1rem;
    font-family: $font-family-display;
    font-weight: 400;
    font-size: $font-size-5xl;
  }

  p {
    color: $text-secondary;
    font-size: $font-size-lg;
    font-family: $font-family-primary;
    margin-bottom: 3.1rem;
  }
}

/* Form Styles */
.contact-form {
  background: rgba(white, 0.9);
  padding: 2.5rem;
  border-radius: $border-radius-2xl;
  border: 1px solid $border-color;
  backdrop-filter: blur(10px);
  box-shadow: $shadow-soft;
}

.form-row {
  display: grid;
  gap: 1.5rem;

  @media (max-width: 767px) {
    grid-template-columns: 1fr;
    gap: 0;
  }
}

.form-group {
  margin-bottom: 1.5rem;

  label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: $font-weight-semibold;
    color: $text-primary;
    font-family: $font-family-primary;
  }

  input,
  select,
  textarea {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 2px solid $border-color;
    border-radius: $border-radius-lg;
    background-color: white;
    font-size: $font-size-base;
    font-family: $font-family-primary;
    transition: all $transition-base;

    &:focus {
      outline: none;
      border-color: $terracotta;
      box-shadow: 0 0 0 0.2rem rgba($terracotta, 0.25);
    }

    &.error {
      border-color: $danger;
    }

    &::placeholder {
      color: $text-muted;
    }
  }

  textarea {
    resize: vertical;
    min-height: 120px;
  }
}

.error-message {
  color: $danger;
  font-size: $font-size-sm;
  margin-top: 0.5rem;
  display: block;
  font-family: $font-family-primary;
}

.checkbox-group {
  margin-bottom: 1.5rem;

  .checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: $font-weight-normal;
    line-height: $line-height-relaxed;
    font-family: $font-family-primary;

    input[type="checkbox"] {
      display: none;
    }

    .checkmark {
      width: 1.25rem;
      height: 1.25rem;
      border: 2px solid $border-color;
      border-radius: $border-radius-sm;
      position: relative;
      flex-shrink: 0;
      margin-top: 0.125rem;
      transition: all $transition-base;
      background: white;

      &::after {
        content: '✓';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: $font-size-sm;
        font-weight: $font-weight-bold;
        opacity: 0;
        transition: opacity $transition-base;
      }
    }

    input:checked+.checkmark {
      background: $gradient-warm;
      border-color: $terracotta;

      &::after {
        opacity: 1;
      }
    }
  }
}

/* Button */
.btn {
  background: $gradient-warm;
  border: none;
  color: white;
  padding: 1rem 2rem;
  border-radius: $border-radius-lg;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  font-family: $font-family-primary;
  cursor: pointer;
  transition: all $transition-base;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  justify-content: center;

  &:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: $shadow-accent;
  }

  &:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
}

/* Success Message */
.success-message {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(white, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: $border-radius-2xl;
  backdrop-filter: blur(10px);

  .success-content {
    text-align: center;
    padding: 2rem;

    i {
      font-size: $font-size-6xl;
      color: $success;
      margin-bottom: 1.5rem;
    }

    h3 {
      color: $text-primary;
      margin-bottom: 0.5rem;
      font-family: $font-family-display;
    }

    p {
      color: $text-secondary;
      font-family: $font-family-primary;
    }
  }
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
  background: rgba(white, 0.9);
  padding: 2rem;
  border-radius: $border-radius-xl;
  box-shadow: $shadow-soft;
  border: 1px solid $border-color;
  backdrop-filter: blur(10px);

  h3,
  h4 {
    color: $text-primary;
    margin-bottom: 1.5rem;
    font-size: $font-size-xl;
    font-family: $font-family-display;
    font-weight: 400;
    font-size: $font-size-4xl;;
  }
}

.social-card h4 {
  font-size: $font-size-4xl;
  text-align: center;
}
.contact-item {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid $border-color-light;

  &:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
  }

  .contact-icon {
    width: 3rem;
    height: 3rem;
    background: $gradient-warm;
    border-radius: $border-radius-lg;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: $font-size-lg;
    flex-shrink: 0;
  }

  .contact-details {
    h4 {
      color: $text-primary;
      margin-bottom: 0.5rem;
      font-size: $font-size-base;
      font-family: $font-family-primary;
    }

    p {
      color: $text-secondary;
      margin: 0 0 0.25rem 0;
      line-height: $line-height-normal;
      font-family: $font-family-primary;
    }

    small {
      color: $text-muted;
      font-size: $font-size-sm;
      font-family: $font-family-primary;
    }
  }
}

/* Emergency Card */
.emergency-card {
  background: $gradient-earth !important;
  color: white;
  text-align: center;

  .emergency-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1rem;

    h4 {
      margin: 0;
      color: white;
    }
  }

  p {
    font-family: $font-family-primary;
  }

  .emergency-phone {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    text-decoration: none;
    font-size: $font-size-xl;
    font-weight: $font-weight-semibold;
    margin: 1rem 0 0.5rem 0;
    padding: 1rem 1.5rem;
    background: rgba($sand, 0.3);
    border-radius: $border-radius-lg;
    transition: all $transition-base;
    font-family: $font-family-primary;

    &:hover {
      background: rgba($sand, 0.4);
      transform: translateY(-2px);
    }
  }

  small {
    font-family: $font-family-primary;
  }
}

/* Social Media */
.social-links {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  color: white;
  text-decoration: none;
  border-radius: $circle;
  transition: all $transition-base;
  font-family: $font-family-primary;

  &.facebook {
    background: #1877f2;
  }

  &.instagram {
    background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
  }

  &.youtube {
    background: #ff0000;
  }

  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  }
}


/* Map Section */
.map-section {
  padding: 5rem 0;
  background: $bg-warm;

  .section-header {
    text-align: center;
    margin-bottom: 3rem;

    h2 {
      font-size: $font-size-4xl;
      color: $text-primary;
      margin-bottom: 1rem;
      font-family: $font-family-display;
      font-weight: 400;
    }

    p {
      color: $text-secondary;
      font-size: $font-size-lg;
      font-family: $font-family-primary;
    }
  }

  .map-container {
    background: rgba(white, 0.9);
    border-radius: $border-radius-2xl;
    overflow: hidden;
    box-shadow: $shadow-medium;
    border: 1px solid $border-color;
    backdrop-filter: blur(10px);
  }

  .map-placeholder {
    height: 400px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: $text-muted;
    text-align: center;
    padding: 2rem;

    i {
      font-size: $font-size-6xl;
      margin-bottom: 1.5rem;
      opacity: 0.5;
      color: $sand;
    }

    h3 {
      color: $text-primary;
      font-family: $font-family-display;
      
    }

    p {
      font-family: $font-family-primary;
    }

    .map-directions {
      margin-top: 2rem;
      text-align: left;
      max-width: 300px;

      h4 {
        color: $text-primary;
        margin-bottom: 1rem;
        font-family: $font-family-primary;
      }

      ul {
        list-style: none;
        padding: 0;
        margin: 0;

        li {
          padding: 0.5rem 0;
          color: $text-secondary;
          position: relative;
          padding-left: 1.5rem;
          font-family: $font-family-primary;

          &::before {
            content: '🐪';
            position: absolute;
            left: 0;
            font-size: 1rem;
          }
        }
      }
    }
  }
}

/* Responsive */
@media (max-width: 767px) {
  .contact-hero {
    padding: 8rem 0 3rem;

    h1 {
      font-size: $font-size-3xl;
    }
  }

  .contact-form {
    padding: 0;
    margin: 0;
  }

  .contact-info {
    order: -1; // Affiche les infos avant le formulaire sur mobile
  }
}


</style>