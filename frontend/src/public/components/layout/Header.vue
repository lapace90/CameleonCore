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
                    <li class="nav-item">
                        <router-link to="/services" class="nav-link">SERVICES</router-link>
                    </li>
                    <li class="nav-item">
                        <router-link to="/contact" class="nav-link">CONTACTS</router-link>
                    </li>
                    <li><a @click="goToDevis" style="cursor: pointer;" class="nav-link">
                            Réserver
                        </a></li>
                </ul>

                <!-- Icône admin à droite (desktop) -->
                <!-- <div class="navbar-actions">
                    <router-link to="/admin" class="nav-link">
                        <AppIcon name="settings" />
                    </router-link>
                </div> -->

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
                                <AppIcon name="home" />
                                <span>ACCUEIL</span>
                            </router-link>
                        </li>
                        <li class="mobile-nav-item">
                            <router-link to="/about" class="mobile-nav-link" @click="closeMobileMenu">
                                <AppIcon name="info" />
                                <span>À PROPOS</span>
                            </router-link>
                        </li>

                        <li class="mobile-nav-item">
                            <router-link to="/services" class="mobile-nav-link" @click="closeMobileMenu">
                                <AppIcon name="bell-ring" />
                                <span>SERVICES</span>
                            </router-link>
                        </li>
                        <li class="mobile-nav-item">
                            <router-link to="/contact" class="mobile-nav-link" @click="closeMobileMenu">
                                <AppIcon name="mail" />
                                <span>CONTACTS</span>

                            </router-link>
                        </li>
                        <li class="mobile-nav-item"><a @click="goToDevis" style="cursor: pointer;"
                                class="mobile-nav-link">
                                <AppIcon name="calendar-days" />
                                <span>RÉSERVER</span>
                            </a></li>
                    </ul>

                    <!-- Actions mobiles -->
                    <div class="mobile-actions">
                        <router-link to="/admin" class="mobile-action-btn" @click="closeMobileMenu" >
                            <AppIcon name="settings" />
                            <span>Administration</span>
                        </router-link>
                        <!-- <button class="mobile-action-btn profile-mobile" @click="closeMobileMenu">
                            <AppIcon name="user" />
                            <span>Mon Profil</span>
                        </button> -->
                    </div>

                    <!-- Contact mobile -->
                    <div class="mobile-contact">
                        <div class="contact-item">
                            <AppIcon name="phone" />
                            <span>+33 4 XX XX XX XX</span>
                        </div>
                        <div class="contact-item">
                            <AppIcon name="mail" />
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
        goToDevis() {
            this.closeMobileMenu()
            
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