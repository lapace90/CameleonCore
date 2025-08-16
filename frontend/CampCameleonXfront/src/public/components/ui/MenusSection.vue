<template>
  <section class="menus-section">
    <div class="container">
      <!-- En-tête de section -->
      <div class="section-header">
        <h2 class="section-title-front">Venez découvrir les saveurs du désert</h2>
        <p class="section-subtitle">
          Une cuisine authentique préparée avec amour au cœur du Sahara
        </p>
      </div>

      <!-- Grille des menus -->
      <div class="menus-grid">
        <div 
          v-for="(menu, index) in menus" 
          :key="index"
          class="menu-card"
          :class="{ featured: menu.featured }"
          @click="selectMenu(menu.id)"
        >
          <!-- Image du menu -->
          <div class="menu-image">
            <img :src="menu.image" :alt="menu.title" />
            <div class="image-overlay">
              
              <div class="price-badge">
                <span class="price">{{ menu.price }}€</span>
                <span class="per-person">/ pers</span>
              </div>
              <!-- <div class="menu-badge" v-if="menu.popular">
                <i class="fas fa-star"></i>
                <span>Populaire</span>
              </div> -->
            </div>
          </div>

          <!-- Contenu du menu -->
          <div class="menu-content">
            <div class="menu-header">
              <h3 class="menu-title">{{ menu.title }}</h3>
              <div class="menu-rating">
                <div class="stars">
                  <i v-for="n in 5" :key="n" 
                     :class="n <= menu.rating ? 'fas fa-star' : 'far fa-star'">
                  </i>
                </div>
                <span class="rating-text">({{ menu.reviews }})</span>
              </div>
            </div>

            <p class="menu-description">{{ menu.description }}</p>

            <!-- Plats inclus -->
            <div class="menu-items">
              <h4>Inclus dans ce menu :</h4>
              <ul class="items-list">
                <li v-for="item in menu.items" :key="item" class="menu-item">
                  <i class="fas fa-utensils"></i>
                  <span>{{ item }}</span>
                </li>
              </ul>
            </div>

            <!-- Options et allergènes -->
            <div class="menu-options">
              <div class="dietary-options">
                <span v-for="option in menu.dietary" :key="option" class="dietary-tag">
                  {{ option }}
                </span>
              </div>
            </div>

            <!-- Bouton de sélection -->
            <div class="menu-action">
              <button class="select-btn" :class="{ selected: selectedMenu === menu.id }">
                <i v-if="selectedMenu === menu.id" class="fas fa-check"></i>
                <i v-else class="fas fa-plus"></i>
                <span>{{ selectedMenu === menu.id ? 'Sélectionné' : 'Choisir ce menu' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Section informations complémentaires -->
      <div class="menus-info">
        <div class="info-cards">
          <div class="info-card">
            <div class="info-icon">
              <i class="fas fa-fire"></i>
            </div>
            <h4>Cuisine au feu de bois</h4>
            <p>Préparation traditionnelle qui rehausse les saveurs</p>
          </div>
          <div class="info-card">
            <div class="info-icon">
              <i class="fas fa-leaf"></i>
            </div>
            <h4>Ingrédients locaux</h4>
            <p>Produits frais sourced directement des oasis environnantes</p>
          </div>
          <div class="info-card">
            <div class="info-icon">
              <i class="fas fa-users"></i>
            </div>
            <h4>Service personnalisé</h4>
            <p>Adaptation possible selon vos préférences alimentaires</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import CouscousImg from '@/assets/images/site/couscous.jpg'

export default {
  name: 'MenusSection',
  data() {
    return {
      selectedMenu: null,
      menus: [
        {
          id: 1,
          title: 'Menu Découverte',
          description: 'Une initiation parfaite aux saveurs du désert avec des plats traditionnels revisités.',
          price: 35,
          rating: 4,
          reviews: 127,
          popular: false,
          featured: false,
          image: 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          items: [
            'Soupe d\'orge aux légumes du désert',
            'Tagine de légumes aux épices berbères',
            'Pain traditionnel cuit au sable',
            'Thé à la menthe et pâtisseries'
          ],
          dietary: ['Végétarien', 'Sans gluten']
        },
        {
          id: 2,
          title: 'Menu Authentique',
          description: 'L\'expérience culinaire complète avec les spécialités les plus appréciées de la région.',
          price: 45,
          rating: 5,
          reviews: 203,
          popular: true,
          featured: true,
          image: CouscousImg,
          items: [
            'Méchoui d\'agneau aux herbes du désert',
            'Couscous royal aux sept légumes',
            'Chorba (soupe traditionnelle)',
            'Assortiment de dattes et fruits secs',
            'Thé à la menthe et cornes de gazelle'
          ],
          dietary: ['Halal', 'Traditionnel']
        },
        {
          id: 3,
          title: 'Menu Prestige',
          description: 'Une expérience gastronomique exceptionnelle qui célèbre le raffinement de la cuisine nomade.',
          price: 65,
          rating: 5,
          reviews: 89,
          popular: false,
          featured: false,
          image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          items: [
            'Pastilla aux pigeons et amandes',
            'Tajine d\'agneau aux pruneaux et miel',
            'Poisson grillé aux épices (selon arrivage)',
            'Salade d\'oranges à la cannelle',
            'Plateau de pâtisseries orientales',
            'Digestif aux herbes du désert'
          ],
          dietary: ['Halal', 'Premium', 'Raffiné']
        }
      ]
    }
  },
  methods: {
    selectMenu(menuId) {
      this.selectedMenu = this.selectedMenu === menuId ? null : menuId;
      this.$emit('menu-selected', {
        menuId: this.selectedMenu,
        menu: this.menus.find(m => m.id === menuId)
      });
    }
  }
}
</script>

<style lang="scss" scoped>
@use 'sass:color';
@import '@/assets/styles/variables';

.menus-section {
  padding: 6rem 0;
  background: linear-gradient(
    135deg,
    $cream 0%,
    color.mix($cream, $sand, 70%) 100%
  );
  position: relative;

  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
      radial-gradient(circle at 15% 85%, rgba($terracotta, 0.06) 0%, transparent 50%),
      radial-gradient(circle at 85% 15%, rgba($coffee, 0.04) 0%, transparent 50%);
    pointer-events: none;
  }

  .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
    z-index: 2;
  }
}

// ========================================
// EN-TÊTE DE SECTION
// ========================================
.section-header {
  text-align: center;
  margin-bottom: 4rem;

  .section-title-front {
    font-size: clamp($font-size-3xl, 4vw, $font-size-5xl);
    font-weight: 400;
    color: $text-primary;
    margin-bottom: 1rem;
    font-family: $font-family-display;
    line-height: $line-height-tight;
  }

  .section-subtitle {
    font-size: $font-size-xl;
    color: $text-secondary;
    font-family: $font-family-primary;
    max-width: 600px;
    margin: 0 auto;
    line-height: $line-height-relaxed;
  }
}

// ========================================
// GRILLE DES MENUS
// ========================================
.menus-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 2.5rem;
  margin-bottom: 5rem;

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
}

.menu-card {
  background: rgba(white, 0.95);
  border-radius: $border-radius-2xl;
  overflow: hidden;
  box-shadow: $shadow-soft;
  transition: all $transition-base;
  cursor: pointer;
  border: 2px solid transparent;
  display: flex; // ✅ FLEX pour aligner les boutons
  flex-direction: column; // ✅ Colonne pour pousser le bouton en bas

  &:hover {
    transform: translateY(-8px);
    box-shadow: $shadow-strong;
    border-color: rgba($terracotta, 0.2);
  }

  &.featured {
    border-color: $terracotta;
    position: relative;

    &::before {
      content: 'Recommandé';
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: $gradient-warm;
      color: white;
      padding: 0.5rem 1rem;
      border-radius: $border-radius;
      font-size: $font-size-sm;
      font-weight: $font-weight-semibold;
      z-index: 3;
      font-family: $font-family-primary;
    }
  }
}

// ========================================
// IMAGE DU MENU
// ========================================
.menu-image {
  position: relative;
  height: 250px;
  overflow: hidden;
  flex-shrink: 0; // ✅ Taille fixe pour l'image

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform $transition-base;
  }

  .image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      180deg,
      transparent 0%,
      rgba($coffee, 0.1) 70%,
      rgba($coffee, 0.3) 100%
    );
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1rem;
  }

  .menu-badge {
    background: rgba($terracotta, 0.9);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: $border-radius-lg;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: $font-size-sm;
    font-weight: $font-weight-semibold;
    backdrop-filter: blur(10px);
    font-family: $font-family-primary;

    i {
      color: #ffd700;
    }
  }

  .price-badge {
    background: rgba(white, 0.95);
    color: $text-primary;
    padding: 0.75rem 1rem;
    border-radius: $border-radius-lg;
    text-align: center;
    backdrop-filter: blur(10px);
    border: 1px solid rgba($sand, 0.3);

    .price {
      display: block;
      font-size: $font-size-xl;
      font-weight: $font-weight-bold;
      color: $terracotta;
      font-family: $font-family-display;
    }

    .per-person {
      font-size: $font-size-sm;
      color: $text-secondary;
      font-family: $font-family-primary;
    }
  }
}

.menu-card:hover .menu-image img {
  transform: scale(1.05);
}

// ========================================
// CONTENU DU MENU
// ========================================
.menu-content {
  padding: 2rem;
  display: flex; // ✅ FLEX pour organiser le contenu
  flex-direction: column;
  flex-grow: 1; // ✅ Prend tout l'espace disponible

  .menu-header {
    margin-bottom: 1.5rem;

    .menu-title {
      font-size: $font-size-2xl;
      font-weight: $font-weight-semibold;
      color: $text-primary;
      margin-bottom: 0.5rem;
      font-family: $font-family-display;
    }

    .menu-rating {
      display: flex;
      align-items: center;
      gap: 0.5rem;

      .stars {
        color: #ffd700;
        font-size: $font-size-base;
      }

      .rating-text {
        color: $text-muted;
        font-size: $font-size-sm;
        font-family: $font-family-primary;
      }
    }
  }

  .menu-description {
    color: $text-secondary;
    line-height: $line-height-relaxed;
    margin-bottom: 1.5rem;
    font-family: $font-family-primary;
  }

  .menu-items {
    margin-bottom: 1.5rem;

    h4 {
      color: $text-primary;
      font-size: $font-size-base;
      font-weight: $font-weight-semibold;
      margin-bottom: 0.75rem;
      font-family: $font-family-primary;
    }

    .items-list {
      list-style: none;
      padding: 0;
      margin: 0;

      .menu-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0;
        color: $text-secondary;
        font-family: $font-family-primary;

        i {
          color: $terracotta;
          font-size: $font-size-sm;
          width: 16px;
          flex-shrink: 0;
        }
      }
    }
  }

  .menu-options {
    margin-bottom: 1.5rem;

    .dietary-options {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;

      .dietary-tag {
        background: rgba($sand, 0.3);
        color: $text-primary;
        padding: 0.25rem 0.75rem;
        border-radius: $border-radius;
        font-size: $font-size-xs;
        font-weight: $font-weight-medium;
        border: 1px solid rgba($terracotta, 0.2);
        font-family: $font-family-primary;
      }
    }
  }

  .menu-action {
    margin-top: auto; // ✅ POUSSE LE BOUTON EN BAS

    .select-btn {
      width: 100%;
      background: $gradient-warm;
      border: none;
      color: white;
      padding: 1rem 1.5rem;
      border-radius: $border-radius-lg;
      font-size: $font-size-base;
      font-weight: $font-weight-semibold;
      font-family: $font-family-primary;
      cursor: pointer;
      transition: all $transition-base;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;

      &:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba($terracotta, 0.3);
      }

      &.selected {
        background: linear-gradient(135deg, #2d8f63 0%, #22c55e 100%);
      }
    }
  }
}

// ========================================
// INFORMATIONS COMPLÉMENTAIRES
// ========================================
.menus-info {
  .info-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;

    .info-card {
      background: rgba(white, 0.8);
      padding: 2rem;
      border-radius: $border-radius-xl;
      text-align: center;
      backdrop-filter: blur(10px);
      border: 1px solid rgba($terracotta, 0.1);
      transition: all $transition-base;

      &:hover {
        background: rgba(white, 0.95);
        transform: translateY(-3px);
        box-shadow: $shadow-medium;
      }

      .info-icon {
        width: 4rem;
        height: 4rem;
        background: $gradient-warm;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: $font-size-xl;
        margin: 0 auto 1rem;
      }

      h4 {
        color: $text-primary;
        font-size: $font-size-lg;
        font-weight: $font-weight-semibold;
        margin-bottom: 0.5rem;
        font-family: $font-family-primary;
      }

      p {
        color: $text-secondary;
        line-height: $line-height-relaxed;
        font-family: $font-family-primary;
      }
    }
  }
}

// ========================================
// ANIMATIONS
// ========================================
.menus-section {
  animation: fadeInUp 0.8s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>