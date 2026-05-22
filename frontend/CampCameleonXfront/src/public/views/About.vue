<!-- src/public/views/About.vue -->
<template>
  <div class="about-page">
    <!-- Hero Section -->
    <section class="about-hero">
      <div class="container mt-5">
        <div class="hero-content">
          <h1>À propos du Camp Caméléon</h1>
          <p class="hero-subtitle">
            Depuis 12 ans, nous créons des expériences inoubliables
            au cœur du désert marocain, où l'authenticité rencontre le raffinement.
          </p>
        </div>
      </div>
    </section>

    <!-- Story Section -->
    <section class="story-section">
      <div class="container">
        <div class="story-content">
          <div class="story-text">
            <h2>Notre Histoire</h2>
            <p>
              Tout a commencé en 2012 avec un rêve : créer un pont entre les cultures dans l'immensité du Sahara marocain. 
              Amina et Youssef, fondateurs du Camp Caméléon, ont transformé une oasis ancestrale en un havre de paix 
              où se rencontrent voyageurs du monde entier et traditions millénaires du désert.
            </p>
            <p>
              Aujourd'hui, nous accueillons plus de 1500 voyageurs chaque année dans nos tentes traditionnelles 
              rénovées avec soin. Notre philosophie demeure inchangée : offrir une immersion authentique dans 
              la culture berbère tout en préservant le confort et la sérénité que mérite chaque invité.
            </p>
            <div class="story-stats">
              <div class="stat-item">
                <span class="stat-number">12</span>
                <span class="stat-label">Années d'hospitalité</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">1500+</span>
                <span class="stat-label">Voyageurs par an</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">25</span>
                <span class="stat-label">Tentes d'exception</span>
              </div>
            </div>
          </div>
          <div class="story-visual">
            <div class="image-container">
              <img 
                src="@/assets/images/site/145.jpg" 
                alt="Tentes berbères dans le désert" 
                class="story-image"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
      <div class="container">
        <div class="section-header">
          <h2>Nos Valeurs</h2>
          <p>Les principes qui guident chaque moment de votre séjour</p>
        </div>

        <div class="values-grid">
          <div class="value-card" v-for="value in values" :key="value.id">
            <div class="value-icon" :style="{ background: value.gradient }">
              <AppIcon :name="value.icon" />
            </div>
            <h3>{{ value.title }}</h3>
            <p>{{ value.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
      <div class="container">
        <div class="section-header">
          <h2>Notre Équipe</h2>
          <p>Les âmes passionnées qui font de votre séjour une expérience magique</p>
        </div>

        <div class="team-grid">
          <div class="team-member" v-for="member in team" :key="member.id">
            <div class="member-avatar">
              <img :src="member.avatar" :alt="member.name">
            </div>
            <h4>{{ member.name }}</h4>
            <span class="member-role">{{ member.role }}</span>
            <p>{{ member.description }}</p>
            <div class="member-social">
              <a href="#" class="social-link" v-if="member.instagram">
                <SocialIcon name="instagram" />
              </a>
              <a href="#" class="social-link" v-if="member.email">
                <AppIcon name="mail" />
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Experience Section -->
    <section class="experience-section">
      <div class="container">
        <div class="section-header">
          <h2>L'Art de l'Hospitalité Marocaine</h2>
          <p>Une immersion totale dans les traditions du désert</p>
        </div>

        <div class="experience-grid">
          <div class="experience-item" v-for="exp in experiences" :key="exp.id">
            <div class="exp-icon">
              <AppIcon :name="exp.icon" />
            </div>
            <h4>{{ exp.name }}</h4>
            <p>{{ exp.description }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: 'AboutPage',
  data() {
    return {
      values: [
        {
          id: 1,
          title: 'Authenticité Berbère',
          description: 'Nous préservons et partageons les traditions millénaires du peuple berbère avec respect et fierté.',
          icon: 'moon',
          gradient: 'linear-gradient(135deg, #CE5E1A 0%, #D6B190 100%)'
        },
        {
          id: 2,
          title: 'Hospitalité Sacrée',
          description: 'Chaque invité est accueilli selon la tradition marocaine : comme un membre de la famille.',
          icon: 'heart',
          gradient: 'linear-gradient(135deg, #656C97 0%, #8691b8 100%)'
        },
        {
          id: 3,
          title: 'Harmonie avec le Désert',
          description: 'Nous vivons en symbiose avec le Sahara, respectant ses cycles et sa beauté sauvage.',
          icon: 'leaf',
          gradient: 'linear-gradient(135deg, #D6B190 0%, #F3D8C7 100%)'
        },
        {
          id: 4,
          title: 'Artisanat Local',
          description: 'Nous soutenons les artisans locaux et intégrons leur savoir-faire dans chaque détail.',
          icon: 'palette',
          gradient: 'linear-gradient(135deg, #CE5E1A 0%, #656C97 100%)'
        }
      ],
      team: [
        {
          id: 1,
          name: 'Amina El Fassi',
          role: 'Fondatrice & Gardienne des Traditions',
          description: 'Native de la région, Amina veille à préserver l\'authenticité de l\'expérience berbère tout en innovant pour le confort moderne.',
          avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80',
          instagram: true,
          email: true
        },
        {
          id: 2,
          name: 'Youssef Ben Ali',
          role: 'Co-fondateur & Guide du Désert',
          description: 'Connaisseur inégalé du Sahara, Youssef guide nos invités vers les secrets les mieux gardés du désert.',
          avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80',
          instagram: true,
          email: true
        },
        {
          id: 3,
          name: 'Fatima Ouhadi',
          role: 'Cheffe Cuisinière',
          description: 'Fatima vous fait découvrir les saveurs authentiques du Maroc avec des recettes transmises de génération en génération.',
          avatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80',
          instagram: false,
          email: true
        },
        {
          id: 4,
          name: 'Omar Tahiri',
          role: 'Maître Chamelier',
          description: 'Omar et ses dromadaires vous emmènent dans des randonnées inoubliables à travers les dunes dorées.',
          avatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80',
          instagram: true,
          email: true
        }
      ],
      experiences: [
        {
          id: 1,
          name: 'Tentes Royales',
          description: 'Hébergement dans des tentes berbères authentiques avec tout le confort moderne',
          icon: 'tent'
        },
        {
          id: 2,
          name: 'Cuisine Traditionnelle',
          description: 'Dégustez les saveurs du Maroc préparées selon les recettes ancestrales',
          icon: 'utensils'
        },
        {
          id: 3,
          name: 'Randonnées Chamelières',
          description: 'Explorez le désert à dos de dromadaire comme les caravanes d\'autrefois',
          icon: 'route'
        },
        {
          id: 4,
          name: 'Musique Berbère',
          description: 'Soirées authentiques au rythme des instruments traditionnels sous les étoiles',
          icon: 'music'
        }
      ]
    }
  }
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.about-page {
  margin-top: -120px;
}

/* Hero Section */
.about-hero {
  background: $gradient-hero;
  color: white;
  top: -1;
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
    font-size: $font-size-6xl;
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
    font-style: italic;
  }
}

/* Story Section */
.story-section {
  padding: 6rem 0;
  background: $bg-secondary;
}

.story-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;

  @media (max-width: 768px) {
    grid-template-columns: 1fr;
    gap: 3rem;
  }
}

.story-text {
  h2 {
    font-size: $font-size-4xl;
    color: $text-primary;
    margin-bottom: 2rem;
    font-family: $font-family-display;
    font-weight: 400;
  }

  p {
    color: $text-secondary;
    line-height: $line-height-relaxed;
    margin-bottom: 1.5rem;
    font-size: $font-size-lg;
  }
}

.story-stats {
  display: flex;
  gap: 2rem;
  margin-top: 3rem;

  @media (max-width: 768px) {
    justify-content: center;
    flex-wrap: wrap;
  }
}

.stat-item {
  text-align: center;
  padding: 1.5rem;
  background: rgba(white, 0.6);
  border-radius: $border-radius-xl;
  backdrop-filter: blur(10px);
  border: 1px solid rgba($terracotta, 0.1);

  .stat-number {
    display: block;
    font-size: $font-size-4xl;
    font-weight: $font-weight-bold;
    color: $accent;
    margin-bottom: 0.5rem;
    font-family: $font-family-display;
  }

  .stat-label {
    color: $text-secondary;
    font-size: $font-size-sm;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: $font-weight-medium;
  }
}

.story-visual {
  .image-container {
    position: relative;

    .story-image {
      width: 100%;
      height: 500px;
      object-fit: cover;
      border-radius: $border-radius-2xl * 2;
      box-shadow: $shadow-strong;

      @media (max-width: 768px) {
        height: 400px;
      }
    }
  }
}

/* Values Section */
.values-section {
  padding: 6rem 0;
  background: white;
}

.values-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;

  @media (max-width: 1024px) {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (max-width: 640px) {
    grid-template-columns: 1fr;
  }
}

.value-card {
  background: white;
  padding: 2rem 1.5rem;
  border-radius: $border-radius-xl;
  box-shadow: $shadow-soft;
  text-align: center;
  transition: all $transition-base;
  border: 1px solid rgba($border-color, 0.3);

  &:hover {
    transform: translateY(-8px);
    box-shadow: $shadow-strong;
  }
}

.value-icon {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: $border-radius-lg;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
  color: white;
  font-size: 1.25rem;
}

.value-card h3 {
  color: $text-primary;
  margin-bottom: 1rem;
  font-size: $font-size-lg;
  font-family: $font-family-display;
  font-weight: 400;
}

.value-card p {
  color: $text-secondary;
  line-height: $line-height-relaxed;
  font-size: $font-size-sm;
}

/* Team Section */
.team-section {
  padding: 6rem 0;
  background: $bg-secondary;
}

.team-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;

  @media (max-width: 1024px) {
    grid-template-columns: repeat(2, 1fr);
  }

  @media (max-width: 640px) {
    grid-template-columns: 1fr;
  }
}

.team-member {
  background: white;
  padding: 2rem 1.5rem;
  border-radius: $border-radius-xl;
  box-shadow: $shadow-soft;
  text-align: center;
  transition: all $transition-base;

  &:hover {
    transform: translateY(-8px);
    box-shadow: $shadow-strong;
  }
}

.member-avatar {
  margin-bottom: 1.25rem;

  img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 3px solid $cream;
    object-fit: cover;
  }
}

.team-member h4 {
  color: $text-primary;
  margin-bottom: 0.5rem;
  font-size: $font-size-lg;
  font-family: $font-family-display;
  font-weight: 400;
}

.member-role {
  color: $accent;
  font-weight: $font-weight-semibold;
  font-size: $font-size-sm;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: block;
  margin-bottom: 1rem;
}

.team-member p {
  color: $text-secondary;
  line-height: $line-height-relaxed;
  margin-bottom: 1.5rem;
  font-size: $font-size-sm;
}

.member-social {
  display: flex;
  justify-content: center;
  gap: 1rem;
}

.social-link {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: $gradient-warm;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: all $transition-base;
  font-size: $font-size-sm;

  &:hover {
    transform: translateY(-3px);
    box-shadow: $shadow-accent;
  }
}

/* Experience Section */
.experience-section {
  padding: 6rem 0;
  background: white;
}

.experience-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.experience-item {
  background: $bg-secondary;
  padding: 2rem;
  border-radius: $border-radius-xl;
  text-align: center;
  border: 1px solid rgba($border-color, 0.3);
  transition: all $transition-base;

  &:hover {
    transform: translateY(-5px);
    box-shadow: $shadow-soft;
  }
}

.exp-icon {
  width: 3.5rem;
  height: 3.5rem;
  background: $gradient-warm;
  border-radius: $border-radius-lg;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  color: white;
  font-size: 1.5rem;
}

.experience-item h4 {
  color: $text-primary;
  margin-bottom: 1rem;
  font-size: $font-size-lg;
  font-family: $font-family-display;
  font-weight: 400;
}

.experience-item p {
  color: $text-secondary;
  font-size: $font-size-sm;
  line-height: $line-height-relaxed;
}

/* Responsive */
@media (max-width: 768px) {
  .about-hero {
    padding: 6rem 0 3rem;
  }
  
  .story-section,
  .values-section,
  .team-section,
  .experience-section {
    padding: 4rem 0;
  }
  
  .story-stats {
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .values-grid,
  .team-grid {
    gap: 1rem;
  }

  .value-card,
  .team-member {
    padding: 1.5rem 1rem;
  }
}
</style>