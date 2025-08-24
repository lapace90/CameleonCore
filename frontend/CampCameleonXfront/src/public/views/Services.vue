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

