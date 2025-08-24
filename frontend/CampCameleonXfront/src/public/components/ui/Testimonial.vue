<!-- src/public/components/ui/Testimonial.vue -->
<template>
  <div class="testimonial-card">
    <!-- Photos Polaroid avec zoom -->
    <div class="polaroid-photos">
      <div 
        v-for="(photo, index) in photos" 
        :key="index"
        class="polaroid"
        :class="{ 'polaroid-rotated': index % 2 === 1 }"
        :style="{ '--rotation': getRotation(index) }"
        @click="openImageModal(photo)"
      >
        <img :src="photo.url" :alt="photo.alt || 'Souvenir du désert'" />
        <div class="zoom-hint">
          <i class="fas fa-search-plus"></i>
        </div>
      </div>
    </div>
    
    <!-- Contenu du témoignage -->
    <div class="testimonial-content">
      <h4 class="client-name">{{ clientName }}</h4>
      <p class="client-location" v-if="location">{{ location }}</p>
      
      <!-- Texte avec système lire plus/moins -->
      <div class="testimonial-text-container">
        <p 
          class="testimonial-text"
          :class="{ 'expanded': isTextExpanded }"
          @click="toggleText"
        >
          {{ displayText }}
        </p>
        
        <button 
          v-if="needsExpansion"
          @click="toggleText"
          class="read-more-btn"
        >
          {{ isTextExpanded ? 'Lire moins' : 'Lire plus' }}
          <i :class="isTextExpanded ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
        </button>
      </div>
      
      <!-- Note étoiles -->
      <div class="rating">
        <span 
          v-for="star in 5" 
          :key="star"
          class="star"
          :class="{ 'filled': star <= rating }"
        >
          ⭐
        </span>
      </div>
    </div>

    <!-- Modal pour zoom d'image -->
    <Transition name="modal">
      <div v-if="showImageModal" class="image-modal" @click="closeImageModal">
        <div class="modal-content" @click.stop>
          <button class="testimonial-close-btn" @click="closeImageModal">
            <i class="fas fa-times"></i>
          </button>
          <img :src="selectedImage?.url" :alt="selectedImage?.alt" class="zoomed-image" />
          <p class="image-caption" v-if="selectedImage?.alt">{{ selectedImage.alt }}</p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script>
export default {
  name: 'TestimonialCard',
  props: {
    clientName: {
      type: String,
      required: true
    },
    location: {
      type: String,
      default: ''
    },
    testimonialText: {
      type: String,
      required: true
    },
    rating: {
      type: Number,
      default: 5,
      validator: (value) => value >= 1 && value <= 5
    },
    photos: {
      type: Array,
      default: () => [
        {
          url: 'https://images.unsplash.com/photo-1518837695005-2083093ee35b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
          alt: 'Désert doré'
        }
      ]
    },
    maxTextLength: {
      type: Number,
      default: 150 // Caractères avant truncature
    }
  },
  data() {
    return {
      isTextExpanded: false,
      showImageModal: false,
      selectedImage: null
    }
  },
  computed: {
    needsExpansion() {
      return this.testimonialText.length > this.maxTextLength;
    },
    displayText() {
      if (!this.needsExpansion || this.isTextExpanded) {
        return this.testimonialText;
      }
      return this.testimonialText.substring(0, this.maxTextLength) + '...';
    }
  },
  methods: {
    getRotation(index) {
      const rotations = ['-8deg', '5deg', '-3deg', '7deg', '-6deg'];
      return rotations[index % rotations.length];
    },
    toggleText() {
      if (this.needsExpansion) {
        this.isTextExpanded = !this.isTextExpanded;
      }
    },
    openImageModal(image) {
      this.selectedImage = image;
      this.showImageModal = true;
    },
    closeImageModal() {
      this.showImageModal = false;
      this.selectedImage = null;
    }
  },
  beforeUnmount() {
    // Nettoie au cas où
    document.body.style.overflow = '';
  }
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.testimonial-card {
  background: rgba($coffee, 0.9);
  border-radius: $border-radius-2xl;
  padding: 2.5rem;
  margin-bottom: 2rem;
  display: flex;
  gap: 2rem;
  align-items: flex-start;
  backdrop-filter: blur(10px);
  border: 1px solid rgba($terracotta, 0.2);
  transition: all $transition-base;
  position: relative;
  overflow: hidden;

  // Effet de brillance subtile
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
      90deg,
      transparent,
      rgba(white, 0.1),
      transparent
    );
    transition: left 0.5s ease;
  }

  &:hover {
    transform: translateY(-5px);
    box-shadow: $shadow-strong;

    &::before {
      left: 100%;
    }
  }

  @media (max-width: 768px) {
    flex-direction: column;
    text-align: center;
    gap: 1.5rem;
    padding: 2rem;
  }
}

.polaroid-photos {
  display: flex;
  gap: 0.75rem; // Gap réduit pour plus de cohérence
  flex-shrink: 0;
  position: relative;
  align-items: flex-start; // Alignement en haut pour éviter les décalages

  @media (max-width: 768px) {
    justify-content: center;
    gap: 0.5rem;
  }
}

.polaroid {
  background: white;
  padding: 8px; // Padding uniforme sur tous les côtés pour centrer
  padding-bottom: 30px; // Espace en bas pour l'effet polaroid
  border-radius: $border-radius-sm;
  box-shadow: $shadow-medium;
  transform: rotate(var(--rotation));
  transition: all $transition-slow;
  position: relative;
  cursor: pointer;
  width: 90px; // Largeur fixe
  height: 110px; // Hauteur fixe
  flex-shrink: 0; // Empêche la déformation
  display: flex;
  flex-direction: column;
  align-items: center; // Centre le contenu horizontalement

  &:hover {
    transform: rotate(0deg) scale(1.1);
    z-index: 10;
    box-shadow: $shadow-strong;

    .zoom-hint {
      opacity: 1;
      transform: translate(-50%, -50%) scale(1);
    }
  }

  &.polaroid-rotated {
    margin-top: 15px; // Réduit pour la cohérence
  }

  img {
    width: 74px; // Taille fixe pour toutes les images
    height: 74px; // Carré parfait
    object-fit: cover; // Coupe l'image pour garder les proportions
    border-radius: 2px;
    display: block;
  }

  // Indicateur de zoom
  .zoom-hint {
    position: absolute;
    top: 45%; // Centré sur l'image
    left: 50%;
    transform: translate(-50%, -50%) scale(0.8);
    background: rgba($coffee, 0.9);
    color: white;
    width: 24px; // Plus petit
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all $transition-base;
    font-size: 0.75rem; // Icône plus petite
    pointer-events: none;
  }

  // Effet polaroid authentique
  &::after {
    content: '';
    position: absolute;
    bottom: 6px;
    left: 50%;
    transform: translateX(-50%);
    width: 60%;
    height: 1px;
    background: rgba($coffee, 0.1);
  }
}

.testimonial-content {
  flex: 1;
  color: white;

  .client-name {
    font-size: $font-size-xl; // Taille uniforme pour tous
    font-weight: $font-weight-semibold;
    color: $sand;
    margin-bottom: 0.5rem;
    font-family: $font-family-display;
    font-weight: 400;
  }

  .client-location {
    font-size: $font-size-sm; // Taille uniforme
    color: rgba($cream, 0.8);
    margin-bottom: 1rem;
    font-style: italic;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: $font-weight-medium;
  }

  .testimonial-text {
    font-size: $font-size-base; // Taille de base TOUJOURS identique
    line-height: $line-height-relaxed;
    color: rgba(white, 0.95);
    margin-bottom: 1rem;
    font-style: italic;
    position: relative;
    cursor: pointer;
    transition: all $transition-base;

    &:hover {
      color: white;
    }

    &.expanded {
      max-height: none;
    }

    // Guillemets décoratifs
    &::before,
    &::after {
      font-family: $font-family-display;
      font-size: 2rem;
      color: rgba($terracotta, 0.5);
      position: absolute;
    }

    &::before {
      content: '"';
      top: -10px;
      left: -15px;
    }

    &::after {
      content: '"';
      bottom: -25px;
      right: 0px;
    }

    @media (max-width: 768px) {
      &::before,
      &::after {
        display: none;
      }
    }
  }

  .testimonial-text-container {
    margin-bottom: 1.5rem;
  }

  .read-more-btn {
    background: rgba($terracotta, 0.2);
    border: 1px solid rgba($terracotta, 0.4);
    color: $sand;
    padding: 0.5rem 1rem;
    border-radius: $border-radius;
    font-size: $font-size-sm;
    cursor: pointer;
    transition: all $transition-base;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;

    &:hover {
      background: rgba($terracotta, 0.3);
      transform: translateY(-1px);
    }

    i {
      font-size: 0.75rem;
      transition: transform $transition-base;
    }
  }

  .rating {
    display: flex;
    gap: 0.25rem;

    @media (max-width: 768px) {
      justify-content: center;
    }

    .star {
      font-size: $font-size-lg;
      transition: all $transition-base;
      filter: grayscale(100%);

      &.filled {
        filter: grayscale(0%);
        animation: starGlow 2s ease-in-out infinite alternate;
      }
    }
  }
}

// Animation des étoiles
@keyframes starGlow {
  from {
    filter: brightness(1) grayscale(0%);
  }
  to {
    filter: brightness(1.2) grayscale(0%);
  }
}

// Variantes de styles - SANS CHANGEMENT DE TAILLE DE TEXTE
.testimonial-card.compact {
  padding: 2rem; // Seul le padding change
  
  .polaroid {
    width: 75px; // Plus petit pour compact
    height: 95px;
    
    img {
      width: 59px;
      height: 59px;
      margin: 0 auto; // Centré
    }
  }
  
  // SUPPRIMÉ : les overrides de taille de texte pour uniformité
}

.testimonial-card.featured {
  border: 2px solid rgba($terracotta, 0.4);
  position: relative;
  
  &::before {
    background: linear-gradient(
      90deg,
      transparent,
      rgba($terracotta, 0.2),
      transparent
    );
  }
  
  &::after {
    content: '✦';
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: $terracotta;
    font-size: 1.5rem;
    animation: starTwinkle 2s ease-in-out infinite;
  }
}

// Responsive pour polaroids
@media (max-width: 768px) {
  .polaroid {
    width: 70px !important; // Taille mobile fixe
    height: 85px !important;
    padding: 6px 6px 20px 6px !important;
    
    img {
      width: 58px !important;
      height: 58px !important;
      margin: 0 auto !important; // Centré sur mobile aussi
    }
    
    &.polaroid-rotated {
      margin-top: 10px !important;
    }
    
    .zoom-hint {
      width: 20px !important;
      height: 20px !important;
      font-size: 0.65rem !important;
    }
  }
  
  .testimonial-card.compact .polaroid {
    width: 60px !important;
    height: 75px !important;
    
    img {
      width: 48px !important;
      height: 48px !important;
      margin: 0 auto !important; // Centré pour compact mobile
    }
  }
}

@keyframes starTwinkle {
  0%, 100% { opacity: 0.6; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.2); }
}

// ========================================
// MODAL D'IMAGE ZOOM
// ========================================
.image-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba($coffee, 0.95);
  backdrop-filter: blur(10px);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;

  .modal-content {
    position: relative;
    max-width: 90vw;
    max-height: 90vh;
    background: white;
    border-radius: $border-radius-xl;
    overflow: hidden;
    box-shadow: $shadow-strong;
    
    .testimonial-close-btn {
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: rgba($coffee, 0.8);
      color: white;
      border: none;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      cursor: pointer;
      z-index: 10;
      transition: all $transition-base;
      display: flex;
      align-items: center;
      justify-content: center;

      &:hover {
        background: $coffee;
        transform: scale(1.1);
      }
    }

    .zoomed-image {
      width: 100%;
      height: auto;
      max-height: 80vh;
      object-fit: contain;
      display: block;
    }

    .image-caption {
      padding: 1rem 2rem;
      background: $bg-secondary;
      color: $text-primary;
      text-align: center;
      font-style: italic;
      margin: 0;
    }
  }
}

// Transitions pour la modal
.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.8);
}
</style>