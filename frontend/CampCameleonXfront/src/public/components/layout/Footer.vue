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
            <a href="https://facebook.com" target="_blank" rel="noopener" class="social-link" aria-label="Facebook">
              <SocialIcon name="facebook" />
            </a>
            <a href="https://instagram.com" target="_blank" rel="noopener" class="social-link" aria-label="Instagram">
              <SocialIcon name="instagram" />
            </a>
            <a href="https://twitter.com" target="_blank" rel="noopener" class="social-link" aria-label="Twitter">
              <SocialIcon name="twitter" />
            </a>
            <a href="https://youtube.com" target="_blank" rel="noopener" class="social-link" aria-label="YouTube">
              <SocialIcon name="youtube" />
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
            <li><router-link to="/privacy" class="footer-link">Confidentialité</router-link></li>
          </ul>
        </div>

        <!-- Services -->
        <div class="footer-section">
          <h4 class="footer-title">Services</h4>
          <ul class="footer-links">
            <li><a @click="goToDevis" style="cursor: pointer;" class="footer-link">
                Réservation en ligne
              </a></li>
            <li><router-link to="/services" class="footer-link">Équipements camping</router-link></li>
            <li><router-link to="/services" class="footer-link">Activités nature</router-link></li>
            <li><router-link to="/about" class="footer-link">Guide et conseils</router-link></li>
          </ul>
        </div>

        <!-- Contact Info -->
        <div class="footer-section">
          <h4 class="footer-title">Contact</h4>
          <div class="contact-info">
            <div class="contact-item">
              <AppIcon name="map-pin" />
              <div>
                <p>Désert de Merzouga</p>
                <p>Erg Chebbi, Maroc</p>
              </div>
            </div>
            <div class="contact-item">
              <AppIcon name="phone" />
              <div>
                <p>+212 6 12 34 56 78</p>
              </div>
            </div>
            <div class="contact-item">
              <AppIcon name="mail" />
              <div>
                <p>contact@campcameleonx.ma</p>
              </div>
            </div>
            <div class="contact-item">
              <AppIcon name="clock" />
              <div>
                <p>Lun - Dim: 24h/24</p>
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
              <input type="email" v-model="email" placeholder="Votre adresse email" required class="newsletter-input">
              <button type="submit" class="newsletter-btn">
                <AppIcon name="send" />
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
            <router-link to="/mentions" class="legal-link">Mentions légales</router-link>
            <router-link to="/privacy" class="legal-link">Politique de confidentialité</router-link>
            <router-link to="/cgu" class="legal-link">CGU</router-link>
            <router-link to="/privacy#cookies" class="legal-link">Cookies</router-link>
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
      return new Date().getFullYear()
    }
  },
  methods: {
    subscribeNewsletter() {
      if (this.email) {
        // Logique d'inscription newsletter
        console.log('Newsletter subscription:', this.email)

        // Simulation d'une inscription réussie
        alert('Merci pour votre inscription !')
        this.email = ''
      }
    },
    goToDevis() {
      if (this.$route.path === '/') {
        // Déjà sur la homepage, juste scroll
        const element = document.getElementById('devis-section');
        if (element) {
          element.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        // Aller à la homepage puis scroll
        this.$router.push('/').then(() => {
          setTimeout(() => {
            const element = document.getElementById('devis-section');
            if (element) {
              element.scrollIntoView({ behavior: 'smooth' });
            }
          }, 300);
        });
      }
    }
  }
}
</script>