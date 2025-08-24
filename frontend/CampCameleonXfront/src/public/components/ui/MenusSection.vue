<template>
  <section class="menus-section">
    <div class="container">
      <!-- En-tête de section -->
      <div class="menu-section-header">
        <h2 class="menu-section-title-front">Venez découvrir les saveurs du désert</h2>
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

