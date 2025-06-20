<template>
  <footer class="public-footer">
    <div class="container">
      <!-- Main Footer Content -->
      <div class="footer-main">
        <!-- Company Info -->
        <div class="footer-section">
          <div class="footer-brand">
            <span class="brand-icon">⛺</span>
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

<style scoped>
.public-footer {
  background: linear-gradient(135deg, #32325d 0%, #1a174d 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.public-footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><radialGradient id="a" cx="50%" cy="0%" r="100%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.03"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><rect width="100" height="20" fill="url(%23a)"/></svg>');
  pointer-events: none;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  position: relative;
  z-index: 1;
}

.footer-main {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 3rem;
  padding: 4rem 0 3rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-section {
  display: flex;
  flex-direction: column;
}

.footer-brand {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.brand-icon {
  font-size: 2rem;
  margin-right: 0.75rem;
  color: #5e72e4;
}

.brand-text {
  background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.footer-description {
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.6;
  margin-bottom: 2rem;
}

.footer-social {
  display: flex;
  gap: 1rem;
}

.social-link {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  color: white;
  text-decoration: none;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.social-link:hover {
  background-color: #5e72e4;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(94, 114, 228, 0.3);
}

.footer-title {
  color: white;
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  position: relative;
}

.footer-title::after {
  content: '';
  position: absolute;
  bottom: -0.5rem;
  left: 0;
  width: 2rem;
  height: 2px;
  background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%);
  border-radius: 1px;
}

.footer-links {
  list-style: none;
  margin: 0;
  padding: 0;
}

.footer-links li {
  margin-bottom: 0.75rem;
}

.footer-link {
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  transition: all 0.15s ease;
  display: inline-block;
}

.footer-link:hover {
  color: #5e72e4;
  transform: translateX(3px);
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.contact-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.contact-item i {
  color: #5e72e4;
  font-size: 1.125rem;
  margin-top: 0.125rem;
  width: 1.25rem;
  flex-shrink: 0;
}

.contact-item p {
  margin: 0;
  color: rgba(255, 255, 255, 0.8);
  line-height: 1.5;
}

.footer-newsletter {
  padding: 3rem 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.newsletter-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
}

.newsletter-text h4 {
  color: white;
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}

.newsletter-text p {
  color: rgba(255, 255, 255, 0.8);
  margin: 0;
}

.newsletter-form {
  flex-shrink: 0;
}

.input-group {
  display: flex;
  background-color: rgba(255, 255, 255, 0.1);
  border-radius: 0.5rem;
  overflow: hidden;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.newsletter-input {
  background: none;
  border: none;
  padding: 1rem 1.5rem;
  color: white;
  min-width: 300px;
  outline: none;
}

.newsletter-input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}

.newsletter-btn {
  background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%);
  border: none;
  color: white;
  padding: 1rem 2rem;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.15s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.newsletter-btn:hover {
  background: linear-gradient(87deg, #4c63d2 0, #7047d2 100%);
  box-shadow: 0 2px 8px rgba(94, 114, 228, 0.3);
}

.footer-bottom {
  padding: 2rem 0;
}

.footer-bottom-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.footer-copyright p {
  margin: 0;
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.875rem;
}

.footer-legal {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}

.legal-link {
  color: rgba(255, 255, 255, 0.6);
  text-decoration: none;
  font-size: 0.875rem;
  transition: color 0.15s ease;
}

.legal-link:hover {
  color: #5e72e4;
}

/* Responsive */
@media (max-width: 768px) {
  .footer-main {
    grid-template-columns: 1fr;
    gap: 2rem;
    padding: 3rem 0 2rem;
  }
  
  .newsletter-content {
    flex-direction: column;
    text-align: center;
    gap: 2rem;
  }
  
  .newsletter-text {
    text-align: center;
  }
  
  .input-group {
    flex-direction: column;
  }
  
  .newsletter-input {
    min-width: auto;
    width: 100%;
  }
  
  .footer-bottom-content {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
  }
  
  .footer-legal {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .newsletter-input {
    min-width: 250px;
  }
  
  .footer-legal {
    flex-direction: column;
    gap: 1rem;
  }
  
  .social-link {
    width: 35px;
    height: 35px;
  }
}
</style>