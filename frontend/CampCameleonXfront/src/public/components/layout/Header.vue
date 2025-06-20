<template>
  <header class="public-header" :class="{ 'scrolled': isScrolled }">
    <div class="container">
      <nav class="navbar">
        <!-- Brand Logo -->
        <router-link to="/home" class="navbar-brand">
          <span class="brand-icon">🏕️</span>
          <span class="brand-text">CampCameleonX</span>
        </router-link>
        
        <!-- Desktop Navigation -->
        <ul class="navbar-nav desktop-nav">
          <li class="nav-item">
            <router-link to="/home" class="nav-link">Accueil</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/about" class="nav-link">À propos</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/services" class="nav-link">Services</router-link>
          </li>
          <li class="nav-item">
            <router-link to="/contact" class="nav-link">Contact</router-link>
          </li>
        </ul>
        
        <!-- CTA Buttons -->
        <div class="navbar-actions desktop-nav">
          <router-link to="/admin" class="btn btn-outline">
            <i class="fas fa-user-shield"></i>
            Admin
          </router-link>
          <a href="#reserve" class="btn btn-primary">
            <i class="fas fa-calendar-plus"></i>
            Réserver
          </a>
        </div>
        
        <!-- Mobile Menu Button -->
        <button 
          class="mobile-menu-btn"
          @click="toggleMobileMenu"
          :class="{ 'active': isMobileMenuOpen }"
          aria-label="Menu mobile"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>
      </nav>
      
      <!-- Mobile Navigation -->
      <div class="mobile-nav" :class="{ 'open': isMobileMenuOpen }">
        <ul class="mobile-nav-list">
          <li class="mobile-nav-item">
            <router-link to="/home" class="mobile-nav-link" @click="closeMobileMenu">
              <i class="fas fa-home"></i>
              Accueil
            </router-link>
          </li>
          <li class="mobile-nav-item">
            <router-link to="/about" class="mobile-nav-link" @click="closeMobileMenu">
              <i class="fas fa-info-circle"></i>
              À propos
            </router-link>
          </li>
          <li class="mobile-nav-item">
            <router-link to="/services" class="mobile-nav-link" @click="closeMobileMenu">
              <i class="fas fa-concierge-bell"></i>
              Services
            </router-link>
          </li>
          <li class="mobile-nav-item">
            <router-link to="/contact" class="mobile-nav-link" @click="closeMobileMenu">
              <i class="fas fa-envelope"></i>
              Contact
            </router-link>
          </li>
          <li class="mobile-nav-divider"></li>
          <li class="mobile-nav-item">
            <router-link to="/admin" class="mobile-nav-link" @click="closeMobileMenu">
              <i class="fas fa-user-shield"></i>
              Administration
            </router-link>
          </li>
          <li class="mobile-nav-item">
            <a href="#reserve" class="mobile-nav-link mobile-nav-cta" @click="closeMobileMenu">
              <i class="fas fa-calendar-plus"></i>
              Réserver maintenant
            </a>
          </li>
        </ul>
      </div>
    </div>
    
    <!-- Mobile Menu Overlay -->
    <div 
      class="mobile-overlay" 
      :class="{ 'active': isMobileMenuOpen }"
      @click="closeMobileMenu"
    ></div>
  </header>
</template>

<script>
export default {
  name: 'PublicHeader',
  data() {
    return {
      isMobileMenuOpen: false,
      isScrolled: false
    }
  },
  methods: {
    toggleMobileMenu() {
      this.isMobileMenuOpen = !this.isMobileMenuOpen;
      
      // Prevent body scroll when menu is open
      if (this.isMobileMenuOpen) {
        document.body.style.overflow = 'hidden';
      } else {
        document.body.style.overflow = '';
      }
    },
    closeMobileMenu() {
      this.isMobileMenuOpen = false;
      document.body.style.overflow = '';
    },
    handleScroll() {
      this.isScrolled = window.scrollY > 50;
    }
  },
  mounted() {
    // Handle scroll effect
    window.addEventListener('scroll', this.handleScroll);
    
    // Close mobile menu on resize
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768 && this.isMobileMenuOpen) {
        this.closeMobileMenu();
      }
    });
    
    // Handle Escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && this.isMobileMenuOpen) {
        this.closeMobileMenu();
      }
    });
  },
  beforeUnmount() {
    window.removeEventListener('scroll', this.handleScroll);
    document.body.style.overflow = '';
  }
}
</script>

<style scoped>
.mobile-nav {
  display: none !important;
}

.mobile-nav-list {
  display: none !important;
}
</style>