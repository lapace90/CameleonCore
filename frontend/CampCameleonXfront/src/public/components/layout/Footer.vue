<template>
  <footer class="public-footer">
    <div class="container">
      <!-- Main Footer Content -->
      <div class="footer-main">
        <!-- Company Info -->
        <div class="footer-section">
          <div class="footer-brand">
            <span class="brand-icon">🦎</span>
            <span class="brand-text">CampCameleonX</span>
          </div>
          <p class="footer-description">
            Votre partenaire de confiance pour des expériences de camping inoubliables. 
            Découvrez la nature autrement avec nos services personnalisés.
          </p>
          <div class="footer-social">
            <a href="#" class="social-link" aria-label="Facebook">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" class="social-link" aria-label="Instagram">
              <i class="fab fa-instagram"></i>
            </a>
            <a href="#" class="social-link" aria-label="Twitter">
              <i class="fab fa-twitter"></i>
            </a>
            <a href="#" class="social-link" aria-label="YouTube">
              <i class="fab fa-youtube"></i>
            </a>
          </div>
        </div>
        
        <!-- Quick Links -->
        <div class="footer-section">
          <h4 class="footer-title">Navigation</h4>
          <ul class="footer-links">
            <li><router-link to="/home" class="footer-link">Accueil</router-link></li>
            <li><router-link to="/about" class="footer-link">À propos</router-link></li>
            <li><router-link to="/services" class="footer-link">Services</router-link></li>
            <li><router-link to="/contact" class="footer-link">Contact</router-link></li>
          </ul>
        </div>
        
        <!-- Services -->
        <div class="footer-section">
          <h4 class="footer-title">Services</h4>
          <ul class="footer-links">
            <li><a href="#" class="footer-link">Réservation en ligne</a></li>
            <li><a href="#" class="footer-link">Équipements camping</a></li>
            <li><a href="#" class="footer-link">Activités nature</a></li>
            <li><a href="#" class="footer-link">Guide et conseils</a></li>
          </ul>
        </div>
        
        <!-- Contact Info -->
        <div class="footer-section">
          <h4 class="footer-title">Contact</h4>
          <div class="contact-info">
            <div class="contact-item">
              <i class="fas fa-map-marker-alt"></i>
              <div>
                <p>123 Rue de la Nature</p>
                <p>75000 Paris, France</p>
              </div>
            </div>
            <div class="contact-item">
              <i class="fas fa-phone"></i>
              <div>
                <p>+33 1 23 45 67 89</p>
              </div>
            </div>
            <div class="contact-item">
              <i class="fas fa-envelope"></i>
              <div>
                <p>contact@campcameleonx.com</p>
              </div>
            </div>
            <div class="contact-item">
              <i class="fas fa-clock"></i>
              <div>
                <p>Lun - Ven: 9h - 18h</p>
                <p>Sam: 9h - 16h</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Newsletter Signup -->
      <div class="footer-newsletter">
        <div class="newsletter-content">
          <div class="newsletter-text">
            <h4>Restez informé</h4>
            <p>Recevez nos dernières offres et actualités directement dans votre boîte mail.</p>
          </div>
          <form class="newsletter-form" @submit.prevent="subscribeNewsletter">
            <div class="input-group">
              <input 
                type="email" 
                v-model="email" 
                placeholder="Votre adresse email"
                required
                class="newsletter-input"
              >
              <button type="submit" class="newsletter-btn">
                <i class="fas fa-paper-plane"></i>
                S'abonner
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Footer Bottom -->
      <div class="footer-bottom">
        <div class="footer-bottom-content">
          <div class="footer-copyright">
            <p>&copy; {{ currentYear }} CampCameleonX. Tous droits réservés.</p>
          </div>
          <div class="footer-legal">
            <a href="#" class="legal-link">Mentions légales</a>
            <a href="#" class="legal-link">Politique de confidentialité</a>
            <a href="#" class="legal-link">CGU</a>
            <a href="#" class="legal-link">Cookies</a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'PublicFooter',
  data() {
    return {
      email: ''
    }
  },
  computed: {
    currentYear() {
      return new Date().getFullYear();
    }
  },
  methods: {
    subscribeNewsletter() {
      if (this.email) {
        // Logique d'inscription newsletter
        console.log('Newsletter subscription:', this.email);
        
        // Simulation d'une inscription réussie
        alert('Merci pour votre inscription !');
        this.email = '';
      }
    }
  }
}
</script>
