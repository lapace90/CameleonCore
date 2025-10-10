<template>
    <header class="desert-header" :class="{ 'scrolled': isScrolled }">
        <div class="container">
            <nav class="navbar">
                <!-- Logo à gauche avec icône caméléon -->
                <router-link to="/" class="navbar-brand">
                    <div class="brand-icon">🦎</div>
                </router-link>
                
                <!-- Navigation centrée (desktop) -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <router-link to="/about" class="nav-link">À PROPOS</router-link>
                    </li>
                    <!-- <li class="nav-item">
                        <router-link to="/reservations" class="nav-link">RÉSERVATIONS</router-link>
                    </li> -->
                    <li class="nav-item">
                        <router-link to="/services" class="nav-link">SERVICES</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/contact" class="nav-link">CONTACTS</router-link>
                    </li>
                </ul>
                
                <!-- Icône profil à droite (desktop) -->
                <div class="navbar-actions">
                    <button class="profile-btn">
                        <i class="fas fa-user nav-link"></i>
                    </button>
                    <router-link to="/admin" class="nav-link">
                        <i class="fas fa-cog"></i>
                    </router-link>
                </div>
                
                <!-- Mobile Menu Button -->
                <button class="mobile-menu-btn" @click="toggleMobileMenu" :class="{ 'active': isMobileMenuOpen }">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
            
            <!--  Menu Mobile -->
            <div class="mobile-menu" :class="{ 'active': isMobileMenuOpen }" @click="closeMobileMenu">
                <div class="mobile-menu-content" @click.stop>
                    <div class="mobile-menu-header">
                        <div class="mobile-brand">
                            <div class="brand-icon">🦎</div>
                            <span>CampCameleonX</span>
                        </div>
                    </div>
                    
                    <ul class="mobile-nav">
                        <li class="mobile-nav-item">
                            <router-link to="/" class="mobile-nav-link" @click="closeMobileMenu">
                                <i class="fas fa-home"></i>
                                <span>ACCUEIL</span>
                            </router-link>
                        </li>
                        <li class="mobile-nav-item">
                            <router-link to="/about" class="mobile-nav-link" @click="closeMobileMenu">
                                <i class="fas fa-info-circle"></i>
                                <span>À PROPOS</span>
                            </router-link>
                        </li>
                        <!-- <li class="mobile-nav-item">
                            <router-link to="/reservations" class="mobile-nav-link" @click="closeMobileMenu">
                                <i class="fas fa-calendar-alt"></i>
                                <span>RÉSERVATIONS</span>
                            </router-link>
                        </li> -->
                        <li class="mobile-nav-item">
                            <router-link to="/services" class="mobile-nav-link" @click="closeMobileMenu">
                                <i class="fas fa-concierge-bell"></i>
                                <span>SERVICES</span>
                            </router-link>
                        </li>
                        <li class="mobile-nav-item">
                            <router-link to="/contact" class="mobile-nav-link" @click="closeMobileMenu">
                                <i class="fas fa-envelope"></i>
                                <span>CONTACTS</span>
                            </router-link>
                        </li>
                    </ul>
                    
                    <!-- Actions mobiles -->
                    <div class="mobile-actions">
                        <router-link to="/admin" class="mobile-action-btn" @click="closeMobileMenu">
                            <i class="fas fa-cog"></i>
                            <span>Administration</span>
                        </router-link>
                        <button class="mobile-action-btn profile-mobile" @click="closeMobileMenu">
                            <i class="fas fa-user"></i>
                            <span>Mon Profil</span>
                        </button>
                    </div>
                    
                    <!-- Contact mobile -->
                    <div class="mobile-contact">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+33 4 XX XX XX XX</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>contact@campcameleonx.com</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script>
export default {
    name: 'DesertHeader',
    data() {
        return {
            isMobileMenuOpen: false,
            isScrolled: false
        }
    },
    methods: {
        toggleMobileMenu() {
            this.isMobileMenuOpen = !this.isMobileMenuOpen;
            // Empêche le scroll du body quand le menu est ouvert
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
        },
        handleClickOutside(event) {
            // Ferme le menu si on clique en dehors
            if (this.isMobileMenuOpen && !this.$el.contains(event.target)) {
                this.closeMobileMenu();
            }
        }
    },
    mounted() {
        window.addEventListener('scroll', this.handleScroll);
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        window.removeEventListener('scroll', this.handleScroll);
        document.removeEventListener('click', this.handleClickOutside);
        // S'assure que le scroll est rétabli
        document.body.style.overflow = '';
    }
}
</script>