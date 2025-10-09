<!-- src/public/views/Home.vue -->
<template>
  <div class="home-page">
    <!-- Hero Section avec votre image -->
    <HeroSection />

    <!-- Section À propos selon maquette -->
    <section class="about-section">
      <div class="container">
        <div class="about-content">
          <div class="about-text">
            <h2 class="about-subtitle">
              À propos de<br>
              <span class="about-title highlight">Camp Caméléon</span>
            </h2>
            <div class="about-divider"></div>
            <h3 class="about-subtitle">
              Le Camp Caméléon, votre oasis de tranquillité et d'aventure.
            </h3>
            <p class="about-description">
              Nichée au cœur du désert marocain, notre maison d'hôtes vous offre une expérience authentique où se mêlent
              traditions berbères et confort moderne. Découvrez l'hospitalité marocaine dans un cadre exceptionnel,
              entre dunes dorées et ciels étoilés.
            </p>
            <div class="about-actions">
              <router-link to="/about" class="btn btn-primary">
                <i class="fas fa-compass"></i>
                En savoir plus
              </router-link>
            </div>
          </div>

          <div class="about-image">
            <div class="image-container">
              <img
                src="https://images.unsplash.com/photo-1544735716-392fe2489ffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80"
                alt="Caravane dans le désert" class="desert-image" />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Activités -->
    <ActivitiesSection displayMode="carousel" />

    <!-- Section Devis -->
    <DevisSection />

    <!-- Section Témoignages -->
    <section class="testimonials-section">
      <div class="container">
        <div class="testimonials-content">
          <!-- Partie gauche - Réservation -->
          <div class="reservation-side">
            <div class="reservation-content">
              <div class="organic-shape">
                <img src="@/assets/images/site/20.jpg" alt="Table dressée dans le désert" class="reservation-image" />
              </div>
              <h2 class="reservation-title">
                Réservez votre séjour<br>
                <span class="highlight">dès maintenant</span>
              </h2>
              <div class="star-accent">✦</div>
            </div>
          </div>

          <!-- Partie droite - Témoignages -->
          <div class="testimonials-side">
            <h2 class="testimonials-title">
              <i>Des souvenirs inoubliables partagés
                par nos invités </i>
            </h2>

            <!-- Utilisation du composant Testimonial -->
            <TestimonialCard client-name="Sophie et Max" location="Paris, France"
              testimonial-text="Un séjour magique au cœur du désert ! Chaque détail était parfait, des tentes confortables aux dîners sous un ciel étoilé incroyable. Nous avons adoré les balades à dos de chameau et l'accueil chaleureux de toute l'équipe. Merci pour cette expérience inoubliable !"
              :rating="5" :photos="[
                {
                  url: 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                }

              ]" class="featured" />

            <TestimonialCard client-name="Mariam et sa famille" location="Casablanca, Maroc"
              testimonial-text="Le Camp Caméléon est bien plus qu'un simple camp: c'est une véritable immersion dans la beauté et la sérénité du désert marocain. Les activités étaient variées et adaptées à toute la famille, et la nourriture locale était délicieuse. Un grand merci pour ces moments précieux !"
              :rating="5" :photos="[
                {
                  url: 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                }
              ]" class="featured" />

            <!-- Bouton voir tous les avis -->
            <div class="view-all-testimonials">
              <router-link to="/testimonials" class="btn btn-secondary btn-sm">
                <i class="fas fa-comments"></i>
                Voir tous les avis
                <span class="testimonials-count "></span>
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
// Import de votre composant Hero
import HeroSection from '@/public/components/ui/Hero.vue'
// Import du composant Testimonial
import TestimonialCard from '@/public/components/ui/Testimonial.vue'
// Import de la section Activités
import ActivitiesSection from '@/public/components/ui/Activities.vue'
// Import de la section Devis
import DevisSection from '@/public/components/ui/DevisSection.vue'


export default {
  name: 'HomePage',
  components: {
    HeroSection,
    TestimonialCard,
    ActivitiesSection,
    DevisSection
  },
  mounted() {
    console.log('Desert Camp Home loaded with local image');
  }
}
</script>

<style scoped>
/* Supprime l'espace vide pour la page Hero */
.home-page {
  margin-top: -45px;
  /* Remonte pour coller au header */
}
</style>