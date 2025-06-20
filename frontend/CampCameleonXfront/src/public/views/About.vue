<template>
  <div class="about-page">
    <!-- Hero Section -->
    <section class="about-hero">
      <div class="container">
        <div class="hero-content">
          <h1>À propos de CampCameleonX</h1>
          <p class="hero-subtitle">
            Depuis 15 ans, nous créons des expériences de camping exceptionnelles 
            au cœur de la nature française.
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
              Tout a commencé en 2009 avec une passion simple : partager la beauté 
              de la nature française avec les familles et aventuriers du monde entier. 
              Marc et Sophie, les fondateurs de CampCameleonX, ont transformé un ancien 
              terrain agricole en un havre de paix pour les amoureux du camping.
            </p>
            <p>
              Aujourd'hui, nous accueillons plus de 2500 campeurs chaque année sur 
              nos 150 emplacements soigneusement aménagés. Notre philosophie reste 
              la même : offrir une expérience authentique tout en respectant 
              l'environnement et le confort de nos visiteurs.
            </p>
            <div class="story-stats">
              <div class="stat-item">
                <span class="stat-number">15</span>
                <span class="stat-label">Années d'expérience</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">2500+</span>
                <span class="stat-label">Clients par an</span>
              </div>
              <div class="stat-item">
                <span class="stat-number">150</span>
                <span class="stat-label">Emplacements</span>
              </div>
            </div>
          </div>
          <div class="story-visual">
            <div class="image-placeholder">
              <i class="fas fa-mountain"></i>
              <p>Photo du camping</p>
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
          <p>Ce qui nous guide chaque jour dans notre mission</p>
        </div>
        
        <div class="values-grid">
          <div class="value-card" v-for="value in values" :key="value.id">
            <div class="value-icon" :style="{ backgroundColor: value.color }">
              <i :class="value.icon"></i>
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
          <p>Les personnes passionnées qui rendent votre séjour inoubliable</p>
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
              <a href="#" class="social-link" v-if="member.linkedin">
                <i class="fab fa-linkedin"></i>
              </a>
              <a href="#" class="social-link" v-if="member.email">
                <i class="fas fa-envelope"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Certifications Section -->
    <section class="certifications-section">
      <div class="container">
        <div class="section-header">
          <h2>Nos Certifications</h2>
          <p>Reconnue pour la qualité et le respect de l'environnement</p>
        </div>
        
        <div class="certifications-grid">
          <div class="certification-item" v-for="cert in certifications" :key="cert.id">
            <div class="cert-logo">
              <i :class="cert.icon"></i>
            </div>
            <h4>{{ cert.name }}</h4>
            <p>{{ cert.description }}</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: 'PublicAbout',
  data() {
    return {
      values: [
        {
          id: 1,
          title: 'Respect de la nature',
          description: 'Nous nous engageons à préserver l\'environnement et à sensibiliser nos visiteurs à l\'écologie.',
          icon: 'fas fa-leaf',
          color: '#2dce89'
        },
        {
          id: 2,
          title: 'Accueil chaleureux',
          description: 'Chaque client est accueilli comme un membre de la famille avec attention et bienveillance.',
          icon: 'fas fa-heart',
          color: '#f5365c'
        },
        {
          id: 3,
          title: 'Qualité du service',
          description: 'Nous visons l\'excellence dans tous nos services pour garantir votre satisfaction.',
          icon: 'fas fa-star',
          color: '#fb6340'
        },
        {
          id: 4,
          title: 'Innovation',
          description: 'Nous modernisons constamment nos équipements et services pour améliorer votre expérience.',
          icon: 'fas fa-lightbulb',
          color: '#11cdef'
        }
      ],
      team: [
        {
          id: 1,
          name: 'Marc Dubois',
          role: 'Fondateur & Directeur',
          description: 'Passionné de nature depuis toujours, Marc a créé CampCameleonX pour partager sa vision du camping authentique.',
          avatar: 'https://via.placeholder.com/120',
          linkedin: true,
          email: true
        },
        {
          id: 2,
          name: 'Sophie Martin',
          role: 'Co-fondatrice & Responsable Accueil',
          description: 'Sophie veille à ce que chaque client vive une expérience mémorable dès son arrivée.',
          avatar: 'https://via.placeholder.com/120',
          linkedin: true,
          email: true
        },
        {
          id: 3,
          name: 'Pierre Leclerc',
          role: 'Responsable Maintenance',
          description: 'Pierre assure que tous nos équipements soient toujours en parfait état de fonctionnement.',
          avatar: 'https://via.placeholder.com/120',
          linkedin: false,
          email: true
        },
        {
          id: 4,
          name: 'Julie Bernard',
          role: 'Animatrice Nature',
          description: 'Julie organise les activités et sensibilise petits et grands à la protection de l\'environnement.',
          avatar: 'https://via.placeholder.com/120',
          linkedin: true,
          email: true
        }
      ],
      certifications: [
        {
          id: 1,
          name: 'Écolabel Européen',
          description: 'Certification environnementale reconnue par l\'UE',
          icon: 'fas fa-leaf'
        },
        {
          id: 2,
          name: 'Qualité Tourisme',
          description: 'Marque d\'État attribuée aux professionnels du tourisme',
          icon: 'fas fa-award'
        },
        {
          id: 3,
          name: 'Accueil Vélo',
          description: 'Services et équipements adaptés aux cyclotouristes',
          icon: 'fas fa-bicycle'
        },
        {
          id: 4,
          name: 'Camping Qualité',
          description: 'Réseau de campings sélectionnés pour leur qualité',
          icon: 'fas fa-certificate'
        }
      ]
    }
  }
}
</script>

<!-- <style scoped>
.about-page {
  padding-top: 70px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Hero Section */
.about-hero {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 4rem 0;
  text-align: center;
}

.hero-content h1 {
  font-size: 3rem;
  margin-bottom: 1rem;
  font-weight: 700;
}

.hero-subtitle {
  font-size: 1.25rem;
  opacity: 0.9;
  max-width: 600px;
  margin: 0 auto;
}

/* Story Section */
.story-section {
  padding: 5rem 0;
  background: white;
}

.story-content {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.story-text h2 {
  font-size: 2.5rem;
  color: #32325d;
  margin-bottom: 2rem;
}

.story-text p {
  color: #8898aa;
  line-height: 1.8;
  margin-bottom: 1.5rem;
  font-size: 1.1rem;
}

.story-stats {
  display: flex;
  gap: 2rem;
  margin-top: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: #5e72e4;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: #8898aa;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.story-visual {
  display: flex;
  justify-content: center;
}

.image-placeholder {
  width: 100%;
  height: 400px;
  background: linear-gradient(135deg, #f8f9fe 0%, #f1f2f6 100%);
  border-radius: 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #8898aa;
  border: 2px dashed #dee2e6;
}

.image-placeholder i {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

/* Values Section */
.values-section {
  padding: 5rem 0;
  background-color: #f8f9fe;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-header h2 {
  font-size: 2.5rem;
  color: #32325d;
  margin-bottom: 1rem;
}

.section-header p {
  color: #8898aa;
  font-size: 1.125rem;
}

.values-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.value-card {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
  text-align: center;
  transition: transform 0.3s ease;
}

.value-card:hover {
  transform: translateY(-5px);
}

.value-icon {
  width: 4rem;
  height: 4rem;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.5rem;
  color: white;
  font-size: 1.5rem;
}

.value-card h3 {
  color: #32325d;
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

.value-card p {
  color: #8898aa;
  line-height: 1.6;
}

/* Team Section */
.team-section {
  padding: 5rem 0;
  background: white;
}

.team-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
}

.team-member {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
  text-align: center;
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.team-member:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.member-avatar {
  margin-bottom: 1.5rem;
}

.member-avatar img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 4px solid #f8f9fe;
  object-fit: cover;
}

.team-member h4 {
  color: #32325d;
  margin-bottom: 0.5rem;
  font-size: 1.25rem;
}

.member-role {
  color: #5e72e4;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: block;
  margin-bottom: 1rem;
}

.team-member p {
  color: #8898aa;
  line-height: 1.6;
  margin-bottom: 1.5rem;
  font-size: 0.9rem;
}

.member-social {
  display: flex;
  justify-content: center;
  gap: 0.75rem;
}

.social-link {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background-color: #f8f9fe;
  color: #8898aa;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  transition: all 0.15s ease;
}

.social-link:hover {
  background-color: #5e72e4;
  color: white;
  transform: translateY(-2px);
}

/* Certifications Section */
.certifications-section {
  padding: 5rem 0;
  background-color: #f8f9fe;
}

.certifications-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
}

.certification-item {
  background: white;
  padding: 1.5rem;
  border-radius: 0.75rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  text-align: center;
  border: 1px solid #e9ecef;
}

.cert-logo {
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #5e72e4 0%, #825ee4 100%);
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  color: white;
  font-size: 1.25rem;
}

.certification-item h4 {
  color: #32325d;
  margin-bottom: 0.5rem;
  font-size: 1rem;
}

.certification-item p {
  color: #8898aa;
  font-size: 0.875rem;
  line-height: 1.5;
}

/* Responsive */
@media (max-width: 768px) {
  .hero-content h1 {
    font-size: 2.5rem;
  }
  
  .story-content {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .story-stats {
    justify-content: center;
  }
  
  .section-header h2 {
    font-size: 2rem;
  }
  
  .team-grid,
  .values-grid {
    grid-template-columns: 1fr;
  }
}
</style> -->