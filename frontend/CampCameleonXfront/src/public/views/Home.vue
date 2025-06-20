<template>
    <div class="public-layout">
        <!-- Header fixe -->
        <PublicHeader />
        <!-- <header class="public-header" :class="{ 'scrolled': isScrolled }">
      <nav class="navbar">
        <router-link to="/" class="navbar-brand">
          <i class="fas fa-mountain brand-icon"></i>
          <span class="brand-text">CampCameleonX</span>
        </router-link>
        
        <ul class="navbar-nav">
          <li class="nav-item">
            <router-link to="/" class="nav-link">Accueil</router-link>
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
        
        <div class="navbar-actions">
          <router-link to="/login" class="btn btn-outline btn-sm">
            <i class="fas fa-user"></i>
            Admin
          </router-link>
          <a href="#reserve" class="btn btn-primary btn-sm">
            <i class="fas fa-calendar-alt"></i>
            Réserver
          </a>
        </div>
      </nav>
    </header> -->

        <!-- Main Content -->
        <main class="main-content">
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="container">
                    <div class="hero-content">
                        <h1 class="hero-title">
                            Découvrez le camping
                            <span class="hero-highlight">autrement</span>
                        </h1>
                        <p class="hero-description">
                            CampCameleonX vous offre une expérience de camping unique au cœur de la nature.
                            Réservez votre emplacement idéal et créez des souvenirs inoubliables.
                        </p>
                        <div class="hero-buttons">
                            <a href="#reserve" class="btn btn-primary btn-lg">
                                <i class="fas fa-calendar-alt"></i>
                                Réserver maintenant
                            </a>
                            <a href="#services" class="btn btn-outline btn-lg">
                                <i class="fas fa-info-circle"></i>
                                Découvrir nos services
                            </a>
                        </div>
                    </div>

                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number">2,500+</span>
                            <span class="stat-label">Clients satisfaits</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">150+</span>
                            <span class="stat-label">Emplacements</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number">5★</span>
                            <span class="stat-label">Note moyenne</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Services Section -->
            <section id="services" class="section section-light">
                <div class="container">
                    <div class="section-header">
                        <h2>Nos services</h2>
                        <p>Tout ce dont vous avez besoin pour un séjour parfait</p>
                    </div>

                    <div class="services-grid">
                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-primary);">
                                <i class="fas fa-campground"></i>
                            </div>
                            <h3>Emplacements premium</h3>
                            <p>Des emplacements spacieux et bien équipés pour tous vos besoins.</p>
                            <ul class="service-features">
                                <li>Électricité</li>
                                <li>Point d'eau</li>
                                <li>WiFi gratuit</li>
                                <li>Vue panoramique</li>
                            </ul>
                        </div>

                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-warm);">
                                <i class="fas fa-hiking"></i>
                            </div>
                            <h3>Activités nature</h3>
                            <p>Découvrez une multitude d'activités pour toute la famille.</p>
                            <ul class="service-features">
                                <li>Randonnées guidées</li>
                                <li>Observation d'oiseaux</li>
                                <li>Ateliers nature</li>
                                <li>Jeux enfants</li>
                            </ul>
                        </div>

                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-primary);">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <h3>Services premium</h3>
                            <p>Des services de qualité pour votre confort et votre sécurité.</p>
                            <ul class="service-features">
                                <li>Surveillance 24h/7j</li>
                                <li>Dépôt de chaises</li>
                                <li>Épicerie</li>
                                <li>Location matériel</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="section">
                <div class="container">
                    <div class="section-header">
                        <h2>Pourquoi choisir CampCameleonX ?</h2>
                    </div>

                    <div class="services-grid">
                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-warm);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3>Réservation simple</h3>
                            <p>Réservez votre emplacement en quelques clics avec notre système en ligne.</p>
                        </div>

                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-primary);">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Prix transparents</h3>
                            <p>Aucun frais caché, des tarifs clairs et compétitifs.</p>
                        </div>

                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-warm);">
                                <i class="fas fa-headset"></i>
                            </div>
                            <h3>Support 24/7</h3>
                            <p>Notre équipe est disponible à tout moment pour vous accompagner.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section class="section section-light">
                <div class="container">
                    <div class="section-header">
                        <h2>Ce que disent nos clients</h2>
                        <p>Des expériences authentiques partagées</p>
                    </div>

                    <div class="services-grid">
                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-warm);">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <h3>Marie Dubois</h3>
                            <p>"Un séjour fantastique ! Les emplacements sont parfaits et le personnel très accueillant.
                                Nous reviendrons certainement."</p>
                            <div style="color: var(--accent); margin-top: 1rem;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>

                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-primary);">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <h3>Jean Martin</h3>
                            <p>"Excellent camping pour les familles, les enfants ont adoré les activités proposées. Très
                                bonne organisation."</p>
                            <div style="color: var(--accent); margin-top: 1rem;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>

                        <div class="service-card">
                            <div class="service-icon" style="background: var(--gradient-warm);">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <h3>Sophie Laurent</h3>
                            <p>"Cadre magnifique, propreté irréprochable et services de qualité. Une expérience camping
                                haut de gamme."</p>
                            <div style="color: var(--accent); margin-top: 1rem;">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section id="reserve" class="cta-section">
                <div class="container">
                    <div class="cta-content">
                        <h2>Prêt pour votre prochaine aventure ?</h2>
                        <p>Réservez dès maintenant votre emplacement idéal et préparez-vous à vivre des moments
                            exceptionnels.</p>
                        <div class="cta-buttons">
                            <a href="#booking" class="btn btn-accent btn-lg">
                                <i class="fas fa-calendar-check"></i>
                                Réserver maintenant
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<script>
export default {
    name: 'HomePage',
    data() {
        return {
            isScrolled: false
        }
    },
    mounted() {
        // Gérer l'effet de scroll sur le header
        window.addEventListener('scroll', this.handleScroll)
    },
    beforeUnmount() {
        window.removeEventListener('scroll', this.handleScroll)
    },
    methods: {
        handleScroll() {
            this.isScrolled = window.scrollY > 50
        }
    }
}
</script>

<style scoped>
/* Styles spécifiques à cette page si nécessaire */
</style>