<template>
  <div class="services-page">
    <!-- Hero Section -->
    <section class="services-hero">
      <div class="container">
        <div class="hero-content">
          <h1>Nos Expériences Désert</h1>
          <p class="hero-subtitle">
            Découvrez tous nos services conçus pour vous offrir une aventure authentique
            au cœur du Sahara marocain.
          </p>
        </div>
      </div>
    </section>

    <!-- Section Menus (votre composant existant) -->
    <MenusSection @menu-selected="handleMenuSelection" />

    <!-- Section Activités (votre composant existant) -->
    <ActivitiesSection displayMode="detailed" sectionTitle="Nos activités détaillées"
      sectionSubtitle="Choisissez votre aventure" :showStats="false" />


    <!-- Section Hébergement -->
    <section class="accommodation-section">
      <div class="container">
        <div class="section-header">
          <h2>Hébergements Authentiques</h2>
          <p>Dormez sous les étoiles dans des installations confortables et traditionnelles</p>
        </div>

        <div class="accommodation-grid">
          <div class="accommodation-card" v-for="accommodation in accommodations" :key="accommodation.id"
            :class="{ 'popular': accommodation.popular }">
            <div class="accommodation-image">
              <img :src="accommodation.image" :alt="accommodation.title" />
              <div class="accommodation-badge" v-if="accommodation.popular">
                <i class="fas fa-star"></i>
                <span>Populaire</span>
              </div>
              <div class="price-badge">
                <span class="price">{{ accommodation.price }}€</span>
                <span class="per-person">/ pers</span>
              </div>
            </div>
            <div class="accommodation-content">
              <h3>{{ accommodation.title }}</h3>
              <p>{{ accommodation.description }}</p>
              <ul class="accommodation-features">
                <li v-for="feature in accommodation.features" :key="feature">
                  <i class="fas fa-check"></i>
                  {{ feature }}
                </li>
              </ul>
              <div class="accommodation-specs">
                <div class="spec">
                  <i class="fas fa-users"></i>
                  <span>{{ accommodation.capacity }} personnes</span>
                </div>
                <div class="spec">
                  <i class="fas fa-bed"></i>
                  <span>{{ accommodation.beds }}</span>
                </div>
              </div>
              <button class="btn btn-primary">
                <i class="fas fa-calendar-plus"></i>
                Réserver
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Services Additionnels -->
    <section class="additional-services">
      <div class="container">
        <div class="section-header">
          <h2>Services & Extras</h2>
          <p>Personnalisez votre aventure avec nos services complémentaires</p>
        </div>

        <div class="services-grid">
          <div class="service-item" v-for="service in extraServices" :key="service.id">
            <div class="service-icon" :style="{ backgroundColor: service.color }">
              <i :class="service.icon"></i>
            </div>
            <div class="service-content">
              <h4>{{ service.title }}</h4>
              <p>{{ service.description }}</p>
              <div class="service-price">
                <span>{{ service.price }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Équipements Inclus -->
    <section class="amenities-section">
      <div class="container">
        <div class="section-header">
          <h2>Inclus dans Votre Séjour</h2>
          <p>Tous ces services sont compris sans supplément</p>
        </div>

        <div class="amenities-grid">
          <div class="amenity-item" v-for="amenity in amenities" :key="amenity.id">
            <div class="amenity-icon">
              <i :class="amenity.icon"></i>
            </div>
            <h5>{{ amenity.title }}</h5>
            <p>{{ amenity.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Section Tarifs Forfaits -->
    <section class="pricing-section">
      <div class="container">
        <div class="section-header">
          <h2>Nos Forfaits Désert</h2>
          <p>Choisissez l'expérience qui vous correspond</p>
        </div>

        <div class="pricing-grid">
          <div class="pricing-card" v-for="plan in pricingPlans" :key="plan.id" :class="{ 'popular': plan.popular }">
            <div class="pricing-header">
              <h3>{{ plan.name }}</h3>
              <div class="pricing-badge" v-if="plan.popular">
                <span>Recommandé</span>
              </div>
            </div>
            <div class="pricing-price">
              <span class="price">{{ plan.price }}€</span>
              <span class="price-unit">{{ plan.unit }}</span>
            </div>
            <div class="pricing-duration">
              <span>{{ plan.duration }}</span>
            </div>
            <ul class="pricing-features">
              <li v-for="feature in plan.features" :key="feature">
                <i class="fas fa-check"></i>
                {{ feature }}
              </li>
            </ul>
            <button class="btn" :class="plan.popular ? 'btn-primary' : 'btn-outline'">
              <i class="fas fa-calendar-check"></i>
              Choisir ce forfait
            </button>
          </div>
        </div>
      </div>
    </section>

    <!-- Section FAQ -->
    <section class="faq-section">
      <div class="container">
        <div class="section-header">
          <h2>Questions Fréquentes</h2>
          <p>Tout ce que vous devez savoir sur votre aventure désert</p>
        </div>

        <div class="faq-list">
          <div class="faq-item" v-for="faq in faqs" :key="faq.id">
            <button class="faq-question" @click="toggleFaq(faq.id)" :class="{ 'active': faq.open }">
              <span>{{ faq.question }}</span>
              <i class="fas fa-chevron-down"></i>
            </button>
            <div class="faq-answer" v-show="faq.open">
              <p>{{ faq.answer }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import ActivitiesSection from '@/public/components/ui/Activities.vue'
import MenusSection from '@/public/components/ui/MenusSection.vue'

export default {
  name: 'ServicesPage',
  components: {
    ActivitiesSection,
    MenusSection
  },
  data() {
    return {
      selectedMenu: null,
      accommodations: [
        {
          id: 1,
          title: 'Tente Berbère Traditionnelle',
          description: 'Authentique tente nomade avec tout le confort moderne. Expérience immersive garantie.',
          image: 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          price: 85,
          capacity: 2,
          beds: '2 matelas au sol',
          popular: false,
          features: [
            'Tente authentique en laine de chameau',
            'Matelas et couvertures berbères',
            'Lanternes traditionnelles',
            'Espace privé de 15m²'
          ]
        },
        {
          id: 2,
          title: 'Tente Confort Étoilée',
          description: 'Le parfait équilibre entre tradition et confort avec vue panoramique sur les dunes.',
          image: 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          price: 120,
          capacity: 2,
          beds: '1 lit double confortable',
          popular: true,
          features: [
            'Tente design avec toit ouvrant',
            'Lit double avec literie premium',
            'Salle de bain privée',
            'Terrasse privée avec vue dunes',
            'Éclairage solaire'
          ]
        },
        {
          id: 3,
          title: 'Suite Famille Oasis',
          description: 'Hébergement spacieux pour familles avec espace enfants et activités dédiées.',
          image: 'https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
          price: 95,
          capacity: 4,
          beds: '1 lit double + 2 lits simples',
          popular: false,
          features: [
            'Espace familial de 25m²',
            'Coin enfants aménagé',
            'Salle de bain familiale',
            'Kit jeux désert inclus',
            'Petit-déjeuner enfants offert'
          ]
        }
      ],
      extraServices: [
        {
          id: 1,
          title: 'Transport 4x4',
          description: 'Transfert depuis Merzouga et excursions dans les dunes en véhicule tout-terrain.',
          icon: 'fas fa-car',
          color: '#CE5E1A',
          price: 'À partir de 25€/trajet'
        },
        {
          id: 2,
          title: 'Guide Privé',
          description: 'Guide berbère dédié pour découvrir les secrets du désert et la culture locale.',
          icon: 'fas fa-user-tie',
          color: '#D6B190',
          price: '40€/demi-journée'
        },
        {
          id: 3,
          title: 'Soirée Musicale',
          description: 'Concert privé de musique traditionnelle autour du feu avec musiciens locaux.',
          icon: 'fas fa-music',
          color: '#656C97',
          price: '60€/soirée'
        },
        {
          id: 4,
          title: 'Atelier Artisanat',
          description: 'Initiation à la poterie, tapis berbère ou cuisine traditionnelle du désert.',
          icon: 'fas fa-palette',
          color: '#41241C',
          price: '20€/personne'
        },
        {
          id: 5,
          title: 'Observation Étoiles',
          description: 'Soirée astronomie avec télescope et guide pour découvrir le ciel du Sahara.',
          icon: 'fas fa-telescope',
          color: '#2d8f63',
          price: '15€/personne'
        },
        {
          id: 6,
          title: 'Massage Détente',
          description: 'Massage relaxant aux huiles d\'argan dans un cadre exceptionnel.',
          icon: 'fas fa-spa',
          color: '#8b6f47',
          price: '45€/séance'
        }
      ],
      amenities: [
        { id: 1, title: 'Sanitaires Écologiques', description: 'Toilettes sèches et douches solaires respectueuses de l\'environnement', icon: 'fas fa-shower' },
        { id: 2, title: 'Sécurité 24/7', description: 'Surveillance discrète et assistance d\'urgence jour et nuit', icon: 'fas fa-shield-alt' },
        { id: 3, title: 'Parking Gardé', description: 'Stationnement sécurisé pour votre véhicule à Merzouga', icon: 'fas fa-parking' },
        { id: 4, title: 'Transferts Inclus', description: 'Transport chameau/4x4 depuis le point de rendez-vous', icon: 'fas fa-route' },
        { id: 5, title: 'WiFi Satellite', description: 'Connexion internet par satellite dans les zones communes', icon: 'fas fa-wifi' },
        { id: 6, title: 'Feu de Camp', description: 'Foyer central pour les soirées conviviales sous les étoiles', icon: 'fas fa-fire' },
        { id: 7, title: 'Éclairage Solaire', description: 'Système d\'éclairage écologique dans tout le campement', icon: 'fas fa-sun' },
        { id: 8, title: 'Eau Potable', description: 'Points d\'eau purifiée disponibles 24h/24', icon: 'fas fa-tint' },
        { id: 9, title: 'Premiers Secours', description: 'Trousse de secours et personnel formé aux urgences', icon: 'fas fa-first-aid' }
      ],
      pricingPlans: [
        {
          id: 1,
          name: 'Évasion Express',
          price: 180,
          unit: '/ pers',
          duration: '1 nuit / 2 jours',
          popular: false,
          features: [
            'Tente berbère traditionnelle',
            '1 dîner + 1 petit-déjeuner',
            'Balade chameau coucher soleil',
            'Soirée musique autour du feu',
            'Transferts 4x4 inclus'
          ]
        },
        {
          id: 2,
          name: 'Immersion Désert',
          price: 320,
          unit: '/ pers',
          duration: '2 nuits / 3 jours',
          popular: true,
          features: [
            'Tente confort étoilée',
            '2 dîners + 2 petits-déjeuners',
            'Randonnée guidée sur les dunes',
            'Atelier artisanat berbère',
            'Observation des étoiles',
            'Tous transferts inclus'
          ]
        },
        {
          id: 3,
          name: 'Odyssée Saharienne',
          price: 580,
          unit: '/ pers',
          duration: '4 nuits / 5 jours',
          popular: false,
          features: [
            'Suite premium avec terrasse',
            'Pension complète gastronomique',
            'Guide privé dédié',
            'Excursions oasis et villages',
            'Massage détente inclus',
            'Soirée musicale privée',
            'Certificat d\'aventurier'
          ]
        }
      ],
      faqs: [
        {
          id: 1,
          question: 'Que dois-je apporter pour le désert ?',
          answer: 'Nous fournissons une liste détaillée après réservation. En général : vêtements chauds pour la nuit, protection solaire, chaussures fermées, lampe frontale. Tout l\'équipement de camp est fourni.',
          open: false
        },
        {
          id: 2,
          question: 'Les repas conviennent-ils aux régimes spéciaux ?',
          answer: 'Absolument ! Nous adaptons nos menus aux régimes végétariens, végétaliens, sans gluten ou halal. Informez-nous de vos besoins lors de la réservation.',
          open: false
        },
        {
          id: 3,
          question: 'Comment se déroule l\'arrivée au camp ?',
          answer: 'Rendez-vous à Merzouga, puis transfert en 4x4 ou à dos de chameau selon votre choix. Le trajet dure 45 minutes et fait partie de l\'expérience !',
          open: false
        },
        {
          id: 4,
          question: 'Y a-t-il de l\'électricité dans le désert ?',
          answer: 'Nos installations fonctionnent à l\'énergie solaire. Vous pouvez recharger vos appareils dans les espaces communs. Les tentes ont un éclairage LED autonome.',
          open: false
        },
        {
          id: 5,
          question: 'Que se passe-t-il en cas de météo défavorable ?',
          answer: 'Le désert se visite toute l\'année ! En cas de conditions exceptionnelles, nous adaptons les activités et proposons des alternatives en toute sécurité.',
          open: false
        }
      ]
    }
  },
  methods: {
    toggleFaq(id) {
      const faq = this.faqs.find(f => f.id === id);
      if (faq) {
        faq.open = !faq.open;
        // Fermer les autres FAQ
        this.faqs.forEach(f => {
          if (f.id !== id) f.open = false;
        });
      }
    },
    handleMenuSelection(menuData) {
      this.selectedMenu = menuData;
      console.log('Menu sélectionné:', menuData);
    }
  }
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.services-page {
  margin-top: -120px; // Background remonte sous header transparent
}

// ========================================
// HERO SECTION
// ========================================
.services-hero {
  background: $gradient-hero;
  color: white;
  padding: 10rem 0 4rem;
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

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
    z-index: 2;
  }

  .hero-content {
    padding-top: 2rem;

    h1 {
      font-size: clamp($font-size-4xl, 5vw, $font-size-6xl);
      margin-bottom: 1.5rem;
      font-family: $font-family-display;
      font-weight: 400;
    }

    .hero-subtitle {
      font-size: $font-size-xl;
      opacity: 0.95;
      max-width: 700px;
      margin: 0 auto;
      line-height: $line-height-relaxed;
      font-family: $font-family-primary;
    }
  }
}

// ========================================
// SECTIONS GÉNÉRALES
// ========================================
.section-header {
  text-align: center;
  margin-bottom: 4rem;

  h2 {
    font-size: clamp($font-size-3xl, 4vw, $font-size-4xl);
    color: $text-primary;
    margin-bottom: 1rem;
    font-family: $font-family-display;
    font-weight: 400;
  }

  p {
    font-size: $font-size-lg;
    color: $text-secondary;
    font-family: $font-family-primary;
    max-width: 600px;
    margin: 0 auto;
  }
}

// ========================================
// HÉBERGEMENTS
// ========================================
.accommodation-section {
  padding: 6rem 0;
  background: linear-gradient(135deg, $bg-secondary 0%, $bg-warm 100%);

  .accommodation-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2.5rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
  }

  .accommodation-card {
    background: rgba(white, 0.95);
    border-radius: $border-radius-2xl;
    overflow: hidden;
    box-shadow: $shadow-soft;
    transition: all $transition-base;
    border: 2px solid transparent;
    display: flex; // ✅ FLEX pour aligner les boutons
    flex-direction: column; // ✅ Colonne pour pousser le bouton en bas

    &:hover {
      transform: translateY(-8px);
      box-shadow: $shadow-strong;
      border-color: rgba($terracotta, 0.3);
    }

    &.popular {
      border-color: $terracotta;
      position: relative;
    }

    .accommodation-image {
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

      .accommodation-badge {
        position: absolute;
        top: 1rem;
        left: 1rem;
        background: $gradient-warm;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: $border-radius;
        font-size: $font-size-sm;
        font-weight: $font-weight-semibold;
        display: flex;
        align-items: center;
        gap: 0.5rem;

        i {
          color: #ffd700;
        }
      }

      .price-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(white, 0.95);
        color: $text-primary;
        padding: 0.75rem 1rem;
        border-radius: $border-radius-lg;
        text-align: center;
        backdrop-filter: blur(10px);

        .price {
          display: block;
          font-size: $font-size-xl;
          font-weight: $font-weight-bold;
          color: $terracotta;
        }

        .per-person {
          font-size: $font-size-sm;
          color: $text-secondary;
        }
      }
    }

    .accommodation-content {
      padding: 2rem;
      display: flex; // ✅ FLEX pour organiser le contenu
      flex-direction: column;
      flex-grow: 1; // ✅ Prend tout l'espace disponible

      h3 {
        font-size: $font-size-2xl;
        color: $text-primary;
        margin-bottom: 1rem;
        font-family: $font-family-display;
      }

      p {
        color: $text-secondary;
        margin-bottom: 1.5rem;
        line-height: $line-height-relaxed;
      }

      .accommodation-features {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem 0;

        li {
          display: flex;
          align-items: center;
          gap: 0.75rem;
          padding: 0.5rem 0;
          color: $text-secondary;

          i {
            color: $terracotta;
            font-size: $font-size-sm;
          }
        }
      }

      .accommodation-specs {
        display: flex;
        gap: 2rem;
        margin-bottom: 2rem;
        padding: 1rem;
        background: rgba($sand, 0.1);
        border-radius: $border-radius;

        .spec {
          display: flex;
          align-items: center;
          gap: 0.5rem;
          color: $text-secondary;

          i {
            color: $terracotta;
          }
        }
      }

      .btn {
        width: 100%;
        background: $gradient-warm;
        border: none;
        color: white;
        padding: 1rem;
        border-radius: $border-radius-lg;
        font-weight: $font-weight-semibold;
        transition: all $transition-base;
        margin-top: auto; // ✅ POUSSE LE BOUTON EN BAS

        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba($terracotta, 0.3);
        }
      }
    }

    &:hover .accommodation-image img {
      transform: scale(1.05);
    }
  }
}

// ========================================
// SERVICES ADDITIONNELS
// ========================================
.additional-services {
  padding: 6rem 0;
  background: $bg-default;

  .services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .service-item {
    background: rgba(white, 0.9);
    padding: 2rem;
    border-radius: $border-radius-xl;
    display: flex;
    gap: 1.5rem;
    transition: all $transition-base;
    border: 1px solid rgba($terracotta, 0.1);

    &:hover {
      background: white;
      transform: translateY(-3px);
      box-shadow: $shadow-medium;
    }

    .service-icon {
      width: 4rem;
      height: 4rem;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: $font-size-xl;
      flex-shrink: 0;
    }

    .service-content {
      h4 {
        color: $text-primary;
        margin-bottom: 0.5rem;
        font-family: $font-family-primary;
      }

      p {
        color: $text-secondary;
        margin-bottom: 1rem;
        font-size: $font-size-sm;
      }

      .service-price {
        font-weight: $font-weight-semibold;
        color: $terracotta;
      }
    }
  }
}

// ========================================
// ÉQUIPEMENTS
// ========================================
.amenities-section {
  padding: 6rem 0;
  background: linear-gradient(135deg, $bg-warm 0%, $bg-secondary 100%);

  .amenities-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr); // ✅ 3 colonnes fixes
    grid-template-rows: repeat(3, 1fr); // ✅ 3 rangées 
    gap: 2rem;
    max-width: 900px; // ✅ Limitation pour ne pas être trop large
    margin: 0 auto;

    @media (max-width: 768px) {
      grid-template-columns: 1fr; // ✅ 1 colonne sur mobile
      grid-template-rows: auto;
      max-width: 300px;
    }

    .amenity-item {
      background: rgba(white, 0.8);
      padding: 2rem;
      border-radius: $border-radius-xl;
      text-align: center;
      transition: all $transition-base;
      border: 1px solid rgba($terracotta, 0.1); // ✅ Bordure subtile

      &:hover {
        background: rgba(white, 0.95);
        transform: translateY(-3px);
        box-shadow: $shadow-medium;
        border-color: rgba($terracotta, 0.3);
      }

      .amenity-icon {
        width: 3.5rem; // ✅ Taille légèrement réduite pour l'équilibre
        height: 3.5rem;
        background: $gradient-warm;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: $font-size-lg; // ✅ Icône proportionnée
        margin: 0 auto 1rem;
      }

      h5 {
        color: $text-primary;
        margin-bottom: 0.75rem; // ✅ Marge ajustée
        font-family: $font-family-primary;
        font-size: $font-size-base; // ✅ Taille ajustée
        font-weight: $font-weight-semibold;
      }

      p {
        color: $text-secondary;
        font-size: $font-size-sm;
        line-height: $line-height-relaxed;
        margin: 0; // ✅ Suppression des marges
      }
    }
  }
}

// ========================================
// TARIFS
// ========================================
.pricing-section {
  padding: 6rem 0;
  background: $bg-default;

  .pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2.5rem;
    max-width: 1000px;
    margin: 0 auto;

    @media (max-width: 768px) {
      grid-template-columns: 1fr;
    }
  }

  .pricing-card {
    background: rgba(white, 0.95);
    border-radius: $border-radius-2xl;
    padding: 2rem;
    text-align: center;
    box-shadow: $shadow-soft;
    transition: all $transition-base;
    border: 2px solid transparent;
    position: relative;
    display: flex; // ✅ FLEX pour aligner les boutons
    flex-direction: column; // ✅ Colonne pour pousser le bouton en bas

    &:hover {
      transform: translateY(-8px);
      box-shadow: $shadow-strong;
    }

    &.popular {
      border-color: $terracotta;
      transform: scale(1.05);

      .pricing-badge {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: $gradient-warm;
        color: white;
        padding: 0.5rem 2rem;
        border-radius: $border-radius-lg;
        font-size: $font-size-sm;
        font-weight: $font-weight-semibold;
      }
    }

    .pricing-header h3 {
      color: $text-primary;
      font-size: $font-size-2xl;
      margin-bottom: 1rem;
      font-family: $font-family-display;
    }

    .pricing-price {
      margin-bottom: 0.5rem;

      .price {
        font-size: $font-size-5xl;
        font-weight: $font-weight-bold;
        color: $terracotta;
        font-family: $font-family-display;
      }

      .price-unit {
        color: $text-secondary;
        font-size: $font-size-base;
      }
    }

    .pricing-duration {
      color: $text-muted;
      margin-bottom: 2rem;
      font-style: italic;
    }

    .pricing-features {
      list-style: none;
      padding: 0;
      margin: 0 0 2rem 0;
      text-align: left;
      flex-grow: 1; // ✅ Prend l'espace disponible

      li {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0;
        color: $text-secondary;

        i {
          color: $terracotta;
          font-size: $font-size-sm;
        }
      }
    }

    .btn {
      width: 100%;
      padding: 1rem;
      border-radius: $border-radius-lg;
      font-weight: $font-weight-semibold;
      transition: all $transition-base;
      margin-top: auto; // ✅ POUSSE LE BOUTON EN BAS

      &.btn-primary {
        background: $gradient-warm;
        border: none;
        color: white;

        &:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba($terracotta, 0.3);
        }
      }

      &.btn-outline {
        background: transparent;
        border: 2px solid $terracotta;
        color: $terracotta;

        &:hover {
          background: $terracotta;
          color: white;
        }
      }
    }
  }
}

// ========================================
// FAQ
// ========================================
.faq-section {
  padding: 6rem 0;
  background: linear-gradient(135deg, $bg-secondary 0%, $bg-warm 100%);

  .faq-list {
    max-width: 800px;
    margin: 0 auto;
  }

  .faq-item {
    background: rgba(white, 0.9);
    margin-bottom: 1rem;
    border-radius: $border-radius-lg;
    overflow: hidden;
    border: 1px solid rgba($terracotta, 0.1);

    .faq-question {
      width: 100%;
      background: none;
      border: none;
      padding: 1.5rem 2rem;
      text-align: left;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: $font-size-lg;
      font-weight: $font-weight-semibold;
      color: $text-primary;
      transition: all $transition-base;

      &:hover {
        background: rgba($sand, 0.1);
      }

      &.active {
        background: rgba($terracotta, 0.1);
        color: $terracotta;

        i {
          transform: rotate(180deg);
        }
      }

      i {
        color: $terracotta;
        transition: transform $transition-base;
      }
    }

    .faq-answer {
      padding: 0 2rem 1.5rem;
      color: $text-secondary;
      line-height: $line-height-relaxed;
    }
  }
}

// ========================================
// ANIMATIONS
// ========================================
.services-page {
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