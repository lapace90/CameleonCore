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
            <button @click="scrollToReviewForm" class="quick-review-btn">
              <AppIcon name="pen" />
              Laisser un avis
            </button>
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
          <button v-for="filter in filters" :key="filter.value" @click="currentFilter = filter.value"
            :class="{ 'active': currentFilter === filter.value }" class="filter-btn">
            {{ filter.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- Loading -->
    <section v-if="loading" class="testimonials-grid-section">
      <div class="container">
        <Loading text="Chargement des témoignages..." variant="light" />
      </div>
    </section>

    <!-- Témoignages -->
    <section v-else class="testimonials-grid-section">
      <div class="container">
        <div class="testimonials-grid">
          <TestimonialCard v-for="testimonial in filteredTestimonials" :key="testimonial.id"
            :client-name="testimonial.client_name" :location="testimonial.location"
            :testimonial-text="testimonial.testimonial_text" :rating="testimonial.rating"
            :photos="testimonial.photos || []" :class="testimonial.featured ? 'featured' : ''" :max-text-length="200" />
        </div>

        <!-- Bouton charger plus -->
        <div class="load-more-section" v-if="hasMoreTestimonials">
          <button @click="loadMore" class="load-more-btn">
            <AppIcon name="plus" />
            Charger plus d'avis
          </button>
        </div>
      </div>
    </section>

    <!-- Section laisser un avis -->
    <section class="leave-review-section">
      <div class="container">
        <div class="review-cta" v-if="!showReviewForm">
          <h2>Partagez votre expérience</h2>
          <p>Vous avez séjourné chez nous ? Laissez-nous votre avis !</p>
          <button @click="showReviewForm = true" class="cta-btn">
            <AppIcon name="pencil" />
            Laisser un avis
          </button>
        </div>

        <div v-else class="review-form-wrapper">
          <button @click="showReviewForm = false" class="close-form-btn">
            <AppIcon name="x" />
          </button>
          <ReviewForm @review-submitted="handleReviewSubmitted" @close="showReviewForm = false" />
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import TestimonialCard from '@/public/components/ui/Testimonial.vue'
import ReviewForm from '@/public/components/ui/ReviewForm.vue'
import ReviewsApi from '@/services/ReviewsApi'
import Loading from '@/shared/components/ui/Loading.vue';

export default {
  name: 'TestimonialsPage',
  components: {
    TestimonialCard,
    ReviewForm,
    Loading
  },

  data() {
    return {
      testimonials: [],
      loading: false,
      showReviewForm: false,
      currentFilter: 'all',
      displayCount: 6,
      filters: [
        { label: 'Tous', value: 'all' },
        { label: 'En couple', value: 'couples' },
        { label: 'En famille', value: 'families' },
        { label: 'Solo', value: 'solo' },
        { label: 'Groupes', value: 'groups' }
      ]
    }
  },

  computed: {
    filteredTestimonials() {
      let filtered = [...this.testimonials]

      if (this.currentFilter !== 'all') {
        filtered = filtered.filter(t => t.category === this.currentFilter)
      }

      return filtered.slice(0, this.displayCount)
    },

    hasMoreTestimonials() {
      const totalFiltered = this.currentFilter === 'all'
        ? this.testimonials.length
        : this.testimonials.filter(t => t.category === this.currentFilter).length

      return this.displayCount < totalFiltered
    },

    averageRating() {
      if (this.testimonials.length === 0) return 0
      const sum = this.testimonials.reduce((acc, t) => acc + (t.rating || 5), 0)
      return sum / this.testimonials.length
    }
  },

  mounted() {
    this.fetchTestimonials()

    if (this.$route?.query?.review === 'true') {
      this.showReviewForm = true
      setTimeout(() => {
        const section = document.querySelector('.leave-review-section')
        if (section) section.scrollIntoView({ behavior: 'smooth' })
      }, 500)
    }
  },

  methods: {
    async fetchTestimonials() {
      this.loading = true
      try {
        // Force le mode public même si admin connecté
        const response = await ReviewsApi.getPublished({
          public_only: true  // Paramètre pour forcer le filtre
        })

        this.testimonials = Array.isArray(response) ? response : []

        console.log('✅ Témoignages chargés:', this.testimonials.length)
      } catch (error) {
        console.error('❌ Erreur lors du chargement des témoignages:', error)
        this.testimonials = []
      } finally {
        this.loading = false
      }
    },
    scrollToReviewForm() {
      this.showReviewForm = true
      this.$nextTick(() => {
        const section = document.querySelector('.leave-review-section')
        if (section) {
          section.scrollIntoView({ behavior: 'smooth' })
        }
      })
    },
    handleReviewSubmitted() {
      this.fetchTestimonials()
      console.log('✅ Merci pour votre avis ! Il sera publié après validation.')
    },

    loadMore() {
      this.displayCount += 3
    }
  }
}
</script>

<style lang="scss" scoped>
@use 'sass:color';
@use '@/assets/styles/variables' as *;

.testimonials-page {
  margin-top: -120px;
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
    margin-top: 2rem;
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

.quick-review-btn {
  margin-top: 2rem;
  background: rgba(white, 0.15);
  border: 2px solid white;
  color: white;
  padding: 1rem;
  border-radius: $border-radius-lg;
  font-size: $font-size-lg;
  font-weight: $font-weight-semibold;
  cursor: pointer;
  transition: all $transition-base;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  backdrop-filter: blur(10px);

  &:hover {
    background: white;
    color: $terracotta;
    transform: translateY(-3px);
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
  background: linear-gradient(135deg, $slate-blue 0%, color.adjust($slate-blue, $lightness: 20%) 100%);
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

.review-form-wrapper {
  position: relative;
  max-width: 700px;
  margin: 0 auto;
  background: rgba(white, 0.4);
  padding: 2rem;
  border-radius: $border-radius-lg;
  box-shadow: $shadow-strong;
}

.close-form-btn {
  position: absolute;
  top: -3rem;
  right: 0;
  background: rgba(white, 0.2);
  border: 2px solid white;
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;

  &:hover {
    background: white;
    color: $terracotta;
    transform: rotate(90deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .testimonials-hero {
    padding: 8.1rem 0 3rem;

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