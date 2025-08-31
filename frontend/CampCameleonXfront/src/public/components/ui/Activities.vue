<template>
    <section class="activities-section">
        <div class="container">
            <div class="activities-content">
                <!-- Titre principal -->
                <div class="section-header">
                    <h2 class="main-title-serif">
                        {{ sectionTitle }}
                    </h2>
                    <p class="title-subtitle" v-if="sectionSubtitle">
                        {{ sectionSubtitle }}
                    </p>
                </div>

                <!-- MODE CARROUSEL (Home) -->
                <div v-if="displayMode === 'carousel'" class="carousel-container">
                    <!-- Carrousel principal -->
                    <div class="main-carousel" @mouseenter="pauseAutoplay" @mouseleave="resumeAutoplay">
                        <div class="carousel-wrapper">
                            <div class="carousel-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }">
                                <div v-for="(activity, index) in activities" :key="index" class="carousel-slide"
                                    :class="{ active: currentSlide === index }">
                                    <div class="slide-content">
                                        <img :src="activity.image" :alt="activity.title" />
                                        <div class="slide-overlay">
                                            <div class="activity-info">
                                                <h3>{{ activity.title }}</h3>
                                                <p>{{ activity.description }}</p>
                                                <div class="activity-badge">
                                                    <i :class="activity.icon"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <button @click="prevSlide" class="nav-btn prev" v-show="activities.length > 1">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button @click="nextSlide" class="nav-btn next" v-show="activities.length > 1">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Indicateurs -->
                    <div class="carousel-dots" v-show="activities.length > 1">
                        <button v-for="(activity, index) in activities" :key="index" @click="goToSlide(index)"
                            :class="{ active: currentSlide === index }" class="dot">
                            <span class="dot-label">{{ activity.shortTitle }}</span>
                        </button>
                    </div>

                    <!-- Progress bar -->
                    <div class="carousel-progress">
                        <div class="progress-bar"
                            :style="{ width: `${((currentSlide + 1) / activities.length) * 100}%` }"></div>
                    </div>
                </div>

                <!-- MODE DÉTAILLÉ (Services) -->
                <div v-else-if="displayMode === 'detailed'" class="detailed-container">
                    <div class="activities-grid">
                        <div v-for="(activity, index) in activities" :key="index" class="activity-card-detailed">
                            <div class="card-image">
                                <img :src="activity.image" :alt="activity.title" />
                                <div class="card-badge">
                                    <i :class="activity.icon"></i>
                                </div>
                            </div>
                            <div class="card-content">
                                <h3>{{ activity.title }}</h3>
                                <p>{{ activity.description }}</p>

                                <!-- Informations supplémentaires pour la vue détaillée -->
                                <div v-if="activity.features" class="activity-features">
                                    <h4>Inclus :</h4>
                                    <ul>
                                        <li v-for="feature in activity.features" :key="feature">
                                            <i class="fas fa-check"></i>
                                            {{ feature }}
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="activity.duration || activity.difficulty" class="activity-meta">
                                    <span v-if="activity.duration" class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        {{ activity.duration }}
                                    </span>
                                    <span v-if="activity.difficulty" class="meta-item">
                                        <i class="fas fa-signal"></i>
                                        {{ activity.difficulty }}
                                    </span>
                                </div>

                                <button class="cta-button">
                                    En savoir plus
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques (optionnelles) -->
                <div v-if="showStats && stats.length" class="activities-stats">
                    <div class="stat-item" v-for="stat in stats" :key="stat.number">
                        <div class="stat-number">{{ stat.number }}</div>
                        <div class="stat-label">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script>
import caravaneImg from '@/assets/images/site/145.jpg'
import tableImg from '@/assets/images/site/20.jpg'
import desertImg from '@/assets/images/site/desert1.jpg'
import dcImg from '@/assets/images/site/dc.jpg'

export default {
    name: 'Activities',
    props: {
        displayMode: {
            type: String,
            default: 'carousel',
            validator: value => ['carousel', 'detailed'].includes(value)
        },
        sectionTitle: {
            type: String,
            default: 'Un programme adapté pour tous les âges et envies:'
        },
        sectionSubtitle: {
            type: String,
            default: 'aventure, détente et découverte.'
        },
        showStats: {
            type: Boolean,
            default: true
        },
        customActivities: {
            type: Array,
            default: null
        }
    },
    data() {
        return {
            currentSlide: 0,
            autoplayInterval: null,
            isAutoplayPaused: false,
            defaultActivities: [
                {
                    title: 'Caravane chamelière',
                    shortTitle: 'Caravane',
                    description: 'Traversez les dunes dorées à dos de chameau au coucher du soleil',
                    image: caravaneImg,
                    icon: 'fas fa-horse-head',
                    duration: '3-4 heures',
                    difficulty: 'Facile',
                    features: [
                        'Guide expérimenté inclus',
                        'Thé traditionnel offert',
                        'Photos souvenirs',
                        'Transport aller-retour'
                    ]
                },
                {
                    title: 'Nuits sous les étoiles',
                    shortTitle: 'Étoiles',
                    description: 'Dormez sous un ciel parsemé d\'étoiles dans le silence du désert',
                    image: dcImg,
                    icon: 'fas fa-star',
                    duration: 'Une nuit',
                    difficulty: 'Facile',
                    features: [
                        'Campement traditionnel',
                        'Repas berbère authentique',
                        'Feu de camp et musique',
                        'Matelas et couvertures fournis'
                    ]
                },
                {
                    title: 'Randonnée sur dunes',
                    shortTitle: 'Randonnée',
                    description: 'Explorez les plus hautes dunes du Sahara avec nos guides experts',
                    image: desertImg,
                    icon: 'fas fa-hiking',
                    duration: '2-6 heures',
                    difficulty: 'Modéré',
                    features: [
                        'Plusieurs circuits disponibles',
                        'Eau et collations incluses',
                        'Guide naturaliste',
                        'Équipement de sécurité'
                    ]
                },
                {
                    title: 'Musique berbère',
                    shortTitle: 'Musique',
                    description: 'Soirées traditionnelles autour du feu avec musiciens locaux',
                    image: caravaneImg,
                    icon: 'fas fa-music',
                    duration: '2-3 heures',
                    difficulty: 'Facile',
                    features: [
                        'Musiciens traditionnels',
                        'Danse participative',
                        'Instruments à essayer',
                        'Thé à la menthe offert'
                    ]
                },
                {
                    title: 'Gastronomie authentique',
                    shortTitle: 'Cuisine',
                    description: 'Savourez la cuisine du désert préparée au feu de bois',
                    image: tableImg,
                    icon: 'fas fa-utensils',
                    duration: '1-2 heures',
                    difficulty: 'Facile',
                    features: [
                        'Ingrédients locaux frais',
                        'Recettes traditionnelles',
                        'Cuisson au feu de bois',
                        'Végétariens bienvenus'
                    ]
                }
            ],
            stats: [
                { number: '15+', label: 'Activités' },
                { number: '100%', label: 'Authentique' },
                { number: '24/7', label: 'Assistance' },
            ]
        }
    },
    computed: {
        activities() {
            return this.customActivities || this.defaultActivities;
        }
    },
    mounted() {
        if (this.displayMode === 'carousel') {
            this.startAutoplay();
        }
    },
    beforeUnmount() {
        this.stopAutoplay();
    },
    watch: {
        displayMode(newMode) {
            if (newMode === 'carousel') {
                this.startAutoplay();
            } else {
                this.stopAutoplay();
            }
        }
    },
    methods: {
        nextSlide() {
            this.currentSlide = (this.currentSlide + 1) % this.activities.length;
        },
        prevSlide() {
            this.currentSlide = this.currentSlide === 0
                ? this.activities.length - 1
                : this.currentSlide - 1;
        },
        goToSlide(index) {
            this.currentSlide = index;
        },
        startAutoplay() {
            this.autoplayInterval = setInterval(() => {
                if (!this.isAutoplayPaused) {
                    this.nextSlide();
                }
            }, 4000);
        },
        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
            }
        },
        pauseAutoplay() {
            this.isAutoplayPaused = true;
        },
        resumeAutoplay() {
            this.isAutoplayPaused = false;
        }
    }
}
</script>
