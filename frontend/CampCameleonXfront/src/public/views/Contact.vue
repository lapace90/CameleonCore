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
                <div class="contact-form-group">
                  <label for="firstName">Prénom *</label>
                  <input type="text" id="firstName" v-model="form.firstName" :class="{ 'error': errors.firstName }"
                    required>
                  <span v-if="errors.firstName" class="error-message">{{ errors.firstName }}</span>
                </div>

                <div class="contact-form-group ">
                  <label for="lastName">Nom *</label>
                  <input type="text" id="lastName" v-model="form.lastName" :class="{ 'error': errors.lastName }"
                    required>
                  <span v-if="errors.lastName" class="error-message">{{ errors.lastName }}</span>
                </div>
              </div>

              <div class="form-row">
                <div class="contact-form-group">
                  <label for="email">Email *</label>
                  <input type="email" id="email" v-model="form.email" :class="{ 'error': errors.email }" required>
                  <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
                </div>

                <div class="contact-form-group">
                  <label for="phone">Téléphone</label>
                  <input type="tel" id="phone" v-model="form.phone">
                </div>
              </div>

              <div class="contact-form-group">
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

              <div class="contact-form-group">
                <label for="message">Message *</label>
                <textarea id="message" v-model="form.message" :class="{ 'error': errors.message }" rows="6"
                  placeholder="Décrivez votre demande en détail..." required></textarea>
                <span v-if="errors.message" class="error-message">{{ errors.message }}</span>
              </div>

              <div class="contact-form-group checkbox-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="form.newsletter">
                  <span class="checkmark"></span>
                  Je souhaite recevoir la newsletter avec les offres et actualités
                </label>
              </div>

              <div class="contact-form-group checkbox-group">
                <label class="checkbox-label">
                  <input type="checkbox" v-model="form.privacy" :class="{ 'error': errors.privacy }" required>
                  <span class="checkmark"></span>
                  J'accepte la <router-link to="/privacy" class="privacy-link" target="_blank">politique de
                    confidentialité</router-link> *
                </label>
                <span v-if="errors.privacy" class="error-message">{{ errors.privacy }}</span>
              </div>

              <button type="submit" class="btn-send" :disabled="submitting">
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
import httpClient from '@/services/httpClient'

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
        this.errors.email = "L'email est requis";
      } else if (!this.isValidEmail(this.form.email)) {
        this.errors.email = "Format d'email invalide";
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
        const response = await httpClient.post('/contact', this.form);

        console.log('✅ Message envoyé:', response.data);

        // Afficher le message de succès
        this.showSuccess = true;

        // Reset du formulaire
        this.resetForm();

        // Masquer le message de succès après 5 secondes
        setTimeout(() => {
          this.showSuccess = false;
        }, 5000);

      } catch (error) {
        console.error('❌ Erreur lors de l\'envoi:', error);
        
        // Gestion des erreurs de validation du backend
        if (error.response?.status === 422) {
          const serverErrors = error.response.data?.errors || {};
          Object.keys(serverErrors).forEach(key => {
            this.errors[key] = serverErrors[key][0]; // Premier message d'erreur
          });
        } else {
          // Erreur générique
          alert(error.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.');
        }
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

<style scoped lang="scss">
@import '@/assets/styles/variables';

.privacy-link {
  color: $terracotta;
  text-decoration: underline;
  font-weight: 500;

  &:hover {
    color: darken($terracotta, 10%);
  }
}
// Map Section - Ajustements Mobile
.map-section {
  @media (max-width: 768px) {
    padding: 2rem 0; // Réduire le padding vertical
  }
}

.map-container {
  @media (max-width: 768px) {
    padding: 1.5rem 1rem; // Ajouter du padding horizontal
  }
}

.map-placeholder {
  @media (max-width: 768px) {
    padding: 2rem 1rem; // Réduire le padding interne
    
    h3 {
      font-size: 1.5rem; // Réduire la taille du titre
    }
    
    .map-directions {
      h4 {
        font-size: 1.1rem; // Réduire la taille du sous-titre
      }
      
      ul {
        
        li {
          margin-bottom: 0.5rem; // Espacement entre les éléments
          font-size: 0.9rem; // Réduire la taille du texte
        }
      }
    }
  }
}
</style>

