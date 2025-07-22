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

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.activities-section {
    padding: 6rem 0;
    background: $gradient-hero;
    color: white;
    position: relative;
    overflow: hidden;

    &::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background:
            radial-gradient(circle at 20% 20%, rgba($sand, 0.08) 0%, transparent 50%),
            radial-gradient(circle at 80% 80%, rgba($terracotta, 0.06) 0%, transparent 50%);
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

.section-header {
    text-align: center;
    margin-bottom: 4rem;

    .main-title-serif {
        font-size: 4rem !important;
        font-weight: 300;
        line-height: $line-height-tight;
        margin-bottom: 1rem;
        font-family: $font-family-heading;
        color: $bg-default;
    }

    .title-subtitle {
        font-size: 2rem;
        font-weight: 300;
        color: $bg-default;
        font-family: $font-family-heading;
    }
}

/* ===== STYLES CARROUSEL (existants) ===== */
.carousel-container {
    position: relative;
    max-width: 900px;
    margin: 0 auto 4rem;
}

.main-carousel {
    position: relative;
    border-radius: $border-radius-2xl;
    // overflow: hidden;
    background: $coffee;
    box-shadow: 0 10px 30px rgba($coffee, 0.4);

    .carousel-wrapper {
        width: 100%;
        height: 480px;
        overflow: hidden;
        position: relative;

        @media (max-width: 768px) {
            height: 300px;
        }
    }

    .carousel-track {
        display: flex;
        transition: transform 0.5s ease-in-out;
        height: 100%;
        will-change: transform;
        position: relative;
    }

    .carousel-slide {
        flex: 0 0 100%;
    }

    .slide-content {
        width: 100%;
        height: 100%;
        position: relative;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }

        .slide-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent 0%, transparent 10%, rgba($coffee, 0.7) 25%);
            color: white;
            padding: 1rem 3rem 0.8rem;
            opacity: 0.9;
            transition: all 0.3s ease;
            transform: translateY(0);
            z-index: 2;

            .activity-info {
                position: relative;

                h3 {
                    font-size: 1.8rem;
                    margin-bottom: 0.5rem;
                    font-family: $font-family-display;
                    color: $sand;
                    font-weight: 400;
                }

                p {
                    font-size: $font-size-base;
                    opacity: 0.95;
                    font-family: $font-family-primary;
                    line-height: $line-height-normal;
                    margin-bottom: 0.5rem;
                }

                .activity-badge {
                    position: absolute;
                    top: -0.5rem;
                    right: 0;
                    width: 40px;
                    height: 40px;
                    background: $gradient-warm;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: $font-size-base;
                    color: white;
                    box-shadow: 0 4px 12px rgba($terracotta, 0.4);

                    @media (max-width: 768px) {
                        width: 35px;
                        height: 35px;
                        font-size: $font-size-sm;
                    }
                }
            }
        }

        &:hover {
            .slide-overlay {
                opacity: 1;
            }
        }
    }
}

.nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 60px;
    height: 60px;
    background: rgba($sand, 0.2);
    border: 2px solid rgba($sand, 0.4);
    border-radius: 50%;
    color: white;
    font-size: $font-size-xl;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
    backdrop-filter: blur(10px);

    &:hover {
        background: rgba($terracotta, 0.8);
        border-color: $terracotta;
        transform: translateY(-50%) scale(1.1);
    }

    &.prev {
        left: -30px;
    }

    &.next {
        right: -30px;
    }

    @media (max-width: 768px) {
        width: 50px;
        height: 50px;
        font-size: $font-size-lg;

        &.prev {
            left: 10px;
        }

        &.next {
            right: 10px;
        }
    }
}

.carousel-dots {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1.5rem;
    margin-bottom: 1rem;

    .dot {
        position: relative;
        padding: 0.5rem 1rem;
        background: rgba($coffee, 0.6);
        border: 1px solid rgba($sand, 0.3);
        border-radius: $border-radius-lg;
        color: rgba(white, 0.7);
        cursor: pointer;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        font-size: $font-size-sm;

        &.active {
            background: rgba($terracotta, 0.8);
            border-color: $terracotta;
            color: white;
            transform: translateY(-2px);
        }

        &:hover:not(.active) {
            background: rgba($sand, 0.3);
            color: white;
        }

        .dot-label {
            font-family: $font-family-primary;
            font-weight: $font-weight-medium;
        }
    }

    @media (max-width: 768px) {
        gap: 0.5rem;
        margin-top: 1rem;

        .dot {
            padding: 0.25rem 0.75rem;
            font-size: $font-size-xs;
        }
    }
}

.carousel-progress {
    margin-top: 1.5rem;
    height: 4px;
    background: rgba($sand, 0.2);
    border-radius: 2px;
    overflow: hidden;

    .progress-bar {
        height: 100%;
        background: $gradient-warm;
        border-radius: 2px;
        transition: width 0.8s ease;
    }
}

/* ===== NOUVEAUX STYLES POUR LA VUE DÉTAILLÉE ===== */
.detailed-container {
    max-width: 1200px;
    margin: 0 auto;
}

.activities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;

    @media (max-width: 768px) {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
}

.activity-card-detailed {
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

    .card-image {
        position: relative;
        height: 250px;
        overflow: hidden;

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .card-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            width: 50px;
            height: 50px;
            background: $gradient-warm;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: $font-size-lg;
            box-shadow: 0 4px 12px rgba($terracotta, 0.4);
        }

        &:hover img {
            transform: scale(1.05);
        }
    }

    .card-content {
        padding: 2rem;

        h3 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: $terracotta;
            font-family: $font-family-display;
            font-weight: 600;
        }

        > p {
            font-size: $font-size-lg;
            line-height: $line-height-relaxed;
            margin-bottom: 1.5rem;
            color: $coffee;
        }

        .activity-features {
            margin-bottom: 1.5rem;

            h4 {
                font-size: $font-size-lg;
                color: $terracotta;
                margin-bottom: 0.75rem;
                font-family: $font-family-primary;
                font-weight: $font-weight-semibold;
            }

            ul {
                list-style: none;
                padding: 0;

                li {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    margin-bottom: 0.5rem;
                    font-size: $font-size-base;
                    color: $coffee;

                    i {
                        color: $terracotta;
                        font-size: $font-size-sm;
                    }
                }
            }
        }

        .activity-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;

            .meta-item {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: $font-size-sm;
                color: rgba(white, 0.7);
                background: rgba($coffee, 0.6);
                padding: 0.5rem 1rem;
                border-radius: $border-radius-lg;

                i {
                    color: $sand;
                }
            }
        }

        .cta-button {
            width: 100%;
            padding: 1rem 1.5rem;
            background: $gradient-warm;
            border: none;
            border-radius: $border-radius-lg;
            color: white;
            font-size: $font-size-base;
            font-weight: $font-weight-semibold;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;

            &:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba($terracotta, 0.4);
            }

            i {
                transition: transform 0.3s ease;
            }

            &:hover i {
                transform: translateX(3px);
            }
        }
    }
}

/* ===== STYLES STATISTIQUES (existants) ===== */
.activities-stats {
    display: flex;
    justify-content: center;
    gap: 3rem;
    margin-top: 3rem;

    @media (max-width: 768px) {
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .stat-item {
        text-align: center;
        padding: 1.5rem;
        background: rgba($sand, 0.1);
        border-radius: $border-radius-xl;
        backdrop-filter: blur(10px);
        border: 1px solid rgba($sand, 0.2);
        transition: all 0.3s ease;

        &:hover {
            background: rgba($sand, 0.2);
            transform: translateY(-3px);
        }

        .stat-number {
            font-size: $font-size-3xl;
            font-weight: $font-weight-bold;
            color: $sand;
            margin-bottom: 0.5rem;
            font-family: $font-family-display;
        }

        .stat-label {
            font-size: $font-size-base;
            color: rgba(white, 0.8);
            font-family: $font-family-primary;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            padding: 1rem;

            .stat-number {
                font-size: $font-size-2xl;
            }

            .stat-label {
                font-size: $font-size-sm;
            }
        }
    }
}

/* Animation d'entrée */
.activities-section {
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