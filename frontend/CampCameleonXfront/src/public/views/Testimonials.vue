<!-- src/public/views/Testimonials.vue -->
<template>
  <div class="testimonials-page">
    <!-- Hero Section -->
    <section class="testimonials-hero">
      <div class="container">
        <div class="hero-content">
          <h1>Ce que disent nos invités</h1>
          <p class="hero-subtitle">
            Découvrez les expériences authentiques partagées par ceux qui ont vécu la magie du désert avec nous.
          </p>
          <div class="stats-summary">
            <div class="stat">
              <span class="number">{{ averageRating.toFixed(1) }}</span>
              <div class="stars">⭐⭐⭐⭐⭐</div>
              <span class="label">Note moyenne</span>
            </div>
            <div class="stat">
              <span class="number">{{ testimonials.length }}+</span>
              <span class="label">Avis clients</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Filtres -->
    <section class="filters-section">
      <div class="container">
        <div class="filters">
          <button 
            v-for="filter in filters" 
            :key="filter.value"
            @click="currentFilter = filter.value"
            :class="{ 'active': currentFilter === filter.value }"
            class="filter-btn"
          >
            {{ filter.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- Témoignages -->
    <section class="testimonials-grid-section">
      <div class="container">
        <div class="testimonials-grid">
          <TestimonialCard
            v-for="testimonial in filteredTestimonials"
            :key="testimonial.id"
            :client-name="testimonial.clientName"
            :location="testimonial.location"
            :testimonial-text="testimonial.text"
            :rating="testimonial.rating"
            :photos="testimonial.photos"
            :class="testimonial.featured ? 'featured' : ''"
            :max-text-length="200"
          />
        </div>

        <!-- Bouton charger plus -->
        <div class="load-more-section" v-if="hasMoreTestimonials">
          <button @click="loadMore" class="load-more-btn">
            <i class="fas fa-plus"></i>
            Charger plus d'avis
          </button>
        </div>
      </div>
    </section>

    <!-- Section laisser un avis -->
    <section class="leave-review-section">
      <div class="container">
        <div class="review-cta">
          <h2>Partagez votre expérience</h2>
          <p>Vous avez séjourné chez nous ? Racontez-nous votre aventure !</p>
          <button class="cta-btn">
            <i class="fas fa-pen"></i>
            Laisser un avis
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import TestimonialCard from '@/public/components/ui/Testimonial.vue'

export default {
  name: 'TestimonialsPage',
  components: {
    TestimonialCard
  },
  data() {
    return {
      currentFilter: 'all',
      displayCount: 6,
      filters: [
        { value: 'all', label: 'Tous les avis' },
        { value: '5', label: '5 étoiles' },
        { value: '4', label: '4 étoiles' },
        { value: 'recent', label: 'Les plus récents' },
        { value: 'families', label: 'Familles' },
        { value: 'couples', label: 'Couples' }
      ],
      testimonials: [
        {
          id: 1,
          clientName: 'Sophie et Max',
          location: 'Paris, France',
          text: 'Un séjour magique au cœur du désert ! Chaque détail était parfait, des tentes confortables aux dîners sous un ciel étoilé incroyable. Nous avons adoré les balades à dos de chameau et l\'accueil chaleureux de toute l\'équipe. L\'expérience authentique berbère nous a transportés dans un autre monde. Les guides connaissent parfaitement la région et nous ont fait découvrir des endroits secrets du Sahara. Merci pour cette expérience inoubliable !',
          rating: 5,
          category: 'couples',
          featured: true,
          photos: [
            {
              url: 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Coucher de soleil dans le désert'
            },
            {
              url: 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Caravane de chameaux'
            }
          ]
        },
        {
          id: 2,
          clientName: 'Mariam et sa famille',
          location: 'Casablanca, Maroc',
          text: 'Le Camp Caméléon est bien plus qu\'un simple camp: c\'est une véritable immersion dans la beauté et la sérénité du désert marocain. Les activités étaient variées et adaptées à toute la famille, et la nourriture locale était délicieuse. Nos enfants ont adoré les ateliers de poterie berbère et les histoires contées autour du feu. Un grand merci pour ces moments précieux !',
          rating: 5,
          category: 'families',
          photos: [
            {
              url: 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Famille dans le désert'
            }
          ]
        },
        {
          id: 3,
          clientName: 'Jean-Luc Martin',
          location: 'Lyon, France',
          text: 'Une expérience extraordinaire ! Le personnel est aux petits soins, la nourriture excellente et les activités variées. J\'ai particulièrement apprécié la randonnée au lever du soleil sur les dunes. Les tentes sont spacieuses et très confortables.',
          rating: 5,
          category: 'couples',
          photos: [
            {
              url: 'https://images.unsplash.com/photo-1509316785289-025f5b846b35?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Lever de soleil sur les dunes'
            }
          ]
        },
        {
          id: 4,
          clientName: 'Emma Thompson',
          location: 'London, UK',
          text: 'Absolutely magical experience! The hospitality was incredible and the desert adventures unforgettable. The traditional berber music evening was the highlight of our trip.',
          rating: 4,
          category: 'couples',
          photos: [
            {
              url: 'https://images.unsplash.com/photo-1558618047-3c8f60f35df1?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Musique berbère traditionnelle'
            }
          ]
        },
        {
          id: 5,
          clientName: 'La famille Dubois',
          location: 'Toulouse, France',
          text: 'Vacances de rêve en famille ! Nos trois enfants ont été émerveillés par cette expérience unique. Les activités sont parfaitement adaptées aux enfants et les guides très patients.',
          rating: 5,
          category: 'families',
          photos: [
            {
              url: 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Enfants jouant dans le sable'
            },
            {
              url: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Tente familiale'
            }
          ]
        },
        {
          id: 6,
          clientName: 'Ahmed et Fatou',
          location: 'Rabat, Maroc',
          text: 'Retour aux sources magnifique ! En tant que Marocains, nous avons redécouvert notre propre culture sous un angle différent. L\'authenticité du lieu et des traditions nous a touchés.',
          rating: 5,
          category: 'couples',
          photos: [
            {
              url: 'https://images.unsplash.com/photo-1569493139819-0bf9ed4b1a96?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
              alt: 'Artisanat traditionnel'
            }
          ]
        }
      ]
    }
  },
  computed: {
    filteredTestimonials() {
      let filtered = [...this.testimonials];
      
      if (this.currentFilter === '5') {
        filtered = filtered.filter(t => t.rating === 5);
      } else if (this.currentFilter === '4') {
        filtered = filtered.filter(t => t.rating === 4);
      } else if (this.currentFilter === 'families') {
        filtered = filtered.filter(t => t.category === 'families');
      } else if (this.currentFilter === 'couples') {
        filtered = filtered.filter(t => t.category === 'couples');
      } else if (this.currentFilter === 'recent') {
        // Les plus récents en premier (par id décroissant)
        filtered.sort((a, b) => b.id - a.id);
      }
      
      return filtered.slice(0, this.displayCount);
    },
    hasMoreTestimonials() {
      return this.displayCount < this.testimonials.length;
    },
    averageRating() {
      const sum = this.testimonials.reduce((acc, t) => acc + t.rating, 0);
      return sum / this.testimonials.length;
    }
  },
  methods: {
    loadMore() {
      this.displayCount += 3;
    }
  }
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.testimonials-page {
  margin-top: -120px; //
}

/* Hero Section */
.testimonials-hero {
  background: $gradient-hero;
  color: white;
  padding: 12rem 0 4rem;
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
  }

  h1 {
    font-size: clamp($font-size-4xl, 5vw, $font-size-5xl);
    margin-bottom: 1.5rem;
    font-family: $font-family-display;
    font-weight: 400;
  }

  .hero-subtitle {
    font-size: $font-size-xl;
    opacity: 0.95;
    max-width: 700px;
    margin: 0 auto 3rem;
    line-height: $line-height-relaxed;
  }

  .stats-summary {
    display: flex;
    justify-content: center;
    gap: 3rem;
    flex-wrap: wrap;

    .stat {
      text-align: center;
      
      .number {
        display: block;
        font-size: $font-size-4xl;
        font-weight: $font-weight-bold;
        margin-bottom: 0.5rem;
        font-family: $font-family-display;
      }

      .stars {
        margin-bottom: 0.5rem;
        font-size: $font-size-lg;
      }

      .label {
        font-size: $font-size-sm;
        opacity: 0.8;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
    }
  }
}

/* Filtres */
.filters-section {
  padding: 2rem 0;
  background: $bg-secondary;
  border-bottom: 1px solid $border-color;
}

.filters {
  display: flex;
  justify-content: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-btn {
  padding: 0.75rem 1.5rem;
  border: 2px solid $border-color;
  background: white;
  color: $text-secondary;
  border-radius: $border-radius-lg;
  cursor: pointer;
  transition: all $transition-base;
  font-weight: $font-weight-medium;

  &:hover,
  &.active {
    background: $gradient-warm;
    border-color: $terracotta;
    color: white;
    transform: translateY(-2px);
  }
}

/* Grille de témoignages */
.testimonials-grid-section {
  padding: 4rem 0;
  background: linear-gradient(135deg, $slate-blue 0%, lighten($slate-blue, 20%) 100%);
}

.testimonials-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
  margin-bottom: 3rem;

  @media (min-width: 768px) {
    gap: 2.5rem;
  }
}

/* Bouton charger plus */
.load-more-section {
  text-align: center;
}

.load-more-btn {
  background: rgba(white, 0.1);
  border: 2px solid rgba(white, 0.3);
  color: white;
  padding: 1rem 2rem;
  border-radius: $border-radius-lg;
  cursor: pointer;
  transition: all $transition-base;
  font-size: $font-size-lg;
  font-weight: $font-weight-medium;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  backdrop-filter: blur(10px);

  &:hover {
    background: rgba(white, 0.2);
    transform: translateY(-3px);
    box-shadow: $shadow-medium;
  }
}

/* Section laisser un avis */
.leave-review-section {
  padding: 5rem 0;
  background: $gradient-warm;
  text-align: center;
}

.review-cta {
  max-width: 600px;
  margin: 0 auto;
  color: white;

  h2 {
    font-size: $font-size-4xl;
    margin-bottom: 1rem;
    font-family: $font-family-display;
    font-weight: 400;
  }

  p {
    font-size: $font-size-lg;
    margin-bottom: 2rem;
    opacity: 0.95;
  }

  .cta-btn {
    background: white;
    color: $terracotta;
    padding: 1rem 2rem;
    border: none;
    border-radius: $border-radius-lg;
    font-size: $font-size-lg;
    font-weight: $font-weight-semibold;
    cursor: pointer;
    transition: all $transition-base;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;

    &:hover {
      transform: translateY(-3px);
      box-shadow: $shadow-strong;
    }
  }
}

/* Responsive */
@media (max-width: 768px) {
  .testimonials-hero {
    padding: 6rem 0 3rem;

    .stats-summary {
      gap: 2rem;
      
      .stat .number {
        font-size: $font-size-3xl;
      }
    }
  }

  .filters {
    gap: 0.5rem;
  }

  .filter-btn {
    padding: 0.5rem 1rem;
    font-size: $font-size-sm;
  }

  .testimonials-grid-section {
    padding: 3rem 0;
  }
}
</style>