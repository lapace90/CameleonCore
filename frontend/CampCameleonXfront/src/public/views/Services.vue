<template>
  <div class="services-page">
    <!-- Hero Section -->
    <section class="services-hero">
      <div class="container">
        <div class="hero-content">
          <h1>Nos Services</h1>
          <p class="hero-subtitle">
            Découvrez tous nos services conçus pour rendre votre séjour 
            inoubliable et confortable.
          </p>
        </div>
      </div>
    </section>

    <!-- Main Services Section -->
    <section class="main-services">
      <div class="container">
        <div class="section-header">
          <h2>Services Principaux</h2>
          <p>Tout ce dont vous avez besoin pour un séjour parfait</p>
        </div>
        
        <div class="services-grid">
          <div class="service-card featured" v-for="service in mainServices" :key="service.id">
            <div class="service-image">
              <div class="image-placeholder" :style="{ background: service.gradient }">
                <i :class="service.icon"></i>
              </div>
            </div>
            <div class="service-content">
              <h3>{{ service.title }}</h3>
              <p>{{ service.description }}</p>
              <ul class="service-features">
                <li v-for="feature in service.features" :key="feature">{{ feature }}</li>
              </ul>
              <div class="service-pricing">
                <span class="price">{{ service.price }}</span>
                <span class="price-unit">{{ service.priceUnit }}</span>
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

    <!-- Additional Services Section -->
    <section class="additional-services">
      <div class="container">
        <div class="section-header">
          <h2>Services Additionnels</h2>
          <p>Des extras pour personnaliser votre expérience</p>
        </div>
        
        <div class="addon-services-grid">
          <div class="addon-service" v-for="addon in addonServices" :key="addon.id">
            <div class="addon-icon" :style="{ backgroundColor: addon.color }">
              <i :class="addon.icon"></i>
            </div>
            <div class="addon-content">
              <h4>{{ addon.title }}</h4>
              <p>{{ addon.description }}</p>
              <div class="addon-price">
                <span>{{ addon.price }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Amenities Section -->
    <section class="amenities-section">
      <div class="container">
        <div class="section-header">
          <h2>Équipements Inclus</h2>
          <p>Profitez de tous ces équipements sans supplément</p>
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

    <!-- Pricing Section -->
    <section class="pricing-section">
      <div class="container">
        <div class="section-header">
          <h2>Nos Tarifs</h2>
          <p>Des prix transparents adaptés à tous les budgets</p>
        </div>
        
        <div class="pricing-grid">
          <div class="pricing-card" v-for="plan in pricingPlans" :key="plan.id" :class="{ 'popular': plan.popular }">
            <div class="pricing-header">
              <h3>{{ plan.name }}</h3>
              <div class="pricing-badge" v-if="plan.popular">
                <span>Populaire</span>
              </div>
            </div>
            <div class="pricing-price">
              <span class="price">{{ plan.price }}€</span>
              <span class="price-unit">{{ plan.unit }}</span>
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

    <!-- FAQ Section -->
    <section class="faq-section">
      <div class="container">
        <div class="section-header">
          <h2>Questions Fréquentes</h2>
          <p>Tout ce que vous devez savoir sur nos services</p>
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
export default {
  name: 'PublicServices',
  data() {
    return {
      mainServices: [
        {
          id: 1,
          title: 'Emplacement Standard',
          description: 'Emplacement spacieux avec tous les équipements de base pour un séjour confortable.',
          icon: 'fas fa-campground',
          gradient: 'linear-gradient(135deg, #5e72e4 0%, #825ee4 100%)',
          features: ['80m² minimum', 'Électricité 16A', 'Point d\'eau à proximité', 'WiFi gratuit'],
          price: '25',
          priceUnit: '/ nuit'
        },
        {
          id: 2,
          title: 'Emplacement Premium',
          description: 'Emplacements privilégiés avec vue panoramique et équipements haut de gamme.',
          icon: 'fas fa-star',
          gradient: 'linear-gradient(135deg, #2dce89 0%, #2dceaa 100%)',
          features: ['120m² minimum', 'Électricité + eau', 'Vue exceptionnelle', 'WiFi premium', 'Parking privé'],
          price: '40',
          priceUnit: '/ nuit'
        },
        {
          id: 3,
          title: 'Forfait Famille',
          description: 'Forfait spécialement conçu pour les familles avec activités incluses.',
          icon: 'fas fa-users',
          gradient: 'linear-gradient(135deg, #fb6340 0%, #fbb140 100%)',
          features: ['Emplacement premium', 'Activités enfants', 'Kit barbecue', 'Petit-déjeuner x2'],
          price: '95',
          priceUnit: '/ séjour'
        }
      ],
      addonServices: [
        {
          id: 1,
          title: 'Location matériel',
          description: 'Tentes, matelas, réchauds et tout l\'équipement nécessaire.',
          icon: 'fas fa-tools',
          color: '#5e72e4',
          price: 'À partir de 15€/jour'
        },
        {
          id: 2,
          title: 'Petit-déjeuner',
          description: 'Petit-déjeuner continental servi chaque matin.',
          icon: 'fas fa-coffee',
          color: '#2dce89',
          price: '8€/personne'
        },
        {
          id: 3,
          title: 'Laverie',
          description: 'Service de lavage et séchage disponible 24h/24.',
          icon: 'fas fa-tshirt',
          color: '#11cdef',
          price: '4€/cycle'
        },
        {
          id: 4,
          title: 'Épicerie',
          description: 'Produits de première nécessité et spécialités locales.',
          icon: 'fas fa-shopping-basket',
          color: '#fb6340',
          price: 'Prix variables'
        }
      ],
      amenities: [
        { id: 1, title: 'Sanitaires', description: 'Blocs sanitaires modernes avec douches chaudes', icon: 'fas fa-shower' },
        { id: 2, title: 'Sécurité', description: 'Surveillance 24h/24 et éclairage nocturne', icon: 'fas fa-shield-alt' },
        { id: 3, title: 'Parkings', description: 'Places de parking gratuites et sécurisées', icon: 'fas fa-parking' },
        { id: 4, title: 'Aires de jeux', description: 'Espaces récréatifs pour enfants', icon: 'fas fa-child' },
        { id: 5, title: 'WiFi', description: 'Connexion internet gratuite dans tout le camping', icon: 'fas fa-wifi' },
        { id: 6, title: 'Barbecue', description: 'Aires de barbecue collectives équipées', icon: 'fas fa-fire' }
      ],
      pricingPlans: [
        {
          id: 1,
          name: 'Découverte',
          price: 20,
          unit: '/ nuit',
          popular: false,
          features: [
            'Emplacement standard 80m²',
            'Électricité incluse',
            'Accès sanitaires',
            'WiFi gratuit',
            'Parking gratuit'
          ]
        },
        {
          id: 2,
          name: 'Confort',
          price: 35,
          unit: '/ nuit',
          popular: true,
          features: [
            'Emplacement premium 120m²',
            'Électricité + point d\'eau',
            'Vue panoramique',
            'WiFi premium',
            'Petit-déjeuner inclus',
            'Kit barbecue'
          ]
        },
        {
          id: 3,
          name: 'Luxe',
          price: 55,
          unit: '/ nuit',
          popular: false,
          features: [
            'Emplacement VIP 150m²',
            'Tous équipements inclus',
            'Service concierge',
            'Activités privées',
            'Petit-déjeuner premium',
            'Service ménage'
          ]
        }
      ],
      faqs: [
        {
          id: 1,
          question: 'Puis-je annuler ma réservation ?',
          answer: 'Oui, vous pouvez annuler votre réservation jusqu\'à 48h avant votre arrivée sans frais. Au-delà, des frais d\'annulation peuvent s\'appliquer.',
          open: false
        },
        {
          id: 2,
          question: 'Les animaux sont-ils acceptés ?',
          answer: 'Oui, les animaux domestiques sont les bienvenus dans notre camping. Un supplément de 5€/nuit/animal s\'applique.',
          open: false
        },
        {
          id: 3,
          question: 'Y a-t-il des commerces à proximité ?',
          answer: 'Oui, vous trouverez une épicerie sur place ainsi que plusieurs commerces dans le village à 2km du camping.',
          open: false
        },
        {
          id: 4,
          question: 'Proposez-vous des activités ?',
          answer: 'Nous organisons régulièrement des randonnées guidées, des ateliers nature et des soirées animations selon la saison.',
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
    }
  }
}
</script>
