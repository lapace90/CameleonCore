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
                                    <!-- <h4>Inclus :</h4> -->
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

                                <button class="cta-button" @click="openActivity(activity)">
                                    En savoir plus
                                    <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistiques (optionnelles) -->
                <div v-if="showStats" class="activities-stats">
                    <div class="stat-item" v-for="stat in stats" :key="stat.number">
                        <div class="stat-number">{{ stat.number }}</div>
                        <div class="stat-label">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- MODALE DÉTAIL MENU -->
    <Teleport to="body">
        <div v-if="selectedActivity" class="cc-modal-backdrop" @click.self="closeModal">
            <div class="cc-modal" role="dialog" aria-modal="true">

                <!-- Détails chargés -->
                <div class="cc-modal-body" v-if="displayedActivity">
                    <header class="cc-modal-head">
                        <h3 class="cc-title">{{ displayedActivity.name || displayedActivity.title }}</h3>
                        <div class="cc-price" v-if="displayedActivity.formatted_price || displayedActivity.price">
                            <span>{{ displayedActivity.formatted_price || (displayedActivity.price + '€') }}</span>
                            <small class="cc-unit">/ pers</small>
                        </div>
                    </header>

                    <img class="cc-modal-image" :src="displayedActivity.image || '/images/placeholder-product.svg'"
                        :alt="displayedActivity.name || displayedActivity.title" loading="lazy" />

                    <p class="cc-desc">{{ displayedActivity.description }}</p>

                    <h4 class="cc-subtitle" v-if="metaForSelected.length">Détails</h4>
                    <ul class="cc-items" v-if="metaForSelected.length">
                        <li v-for="(row, idx) in metaForSelected" :key="idx">
                            <i class="fas fa-check"></i> <strong>{{ row[0] }} :</strong> {{ row[1] }}
                        </li>
                    </ul>

                    <div class="cc-actions">
                        <button class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar-check"></i> Réserver
                        </button>
                        <button class="btn btn-outline btn-sm" @click="closeModal">Fermer</button>
                    </div>
                </div>

                <!-- État de chargement -->
                <div class="cc-modal-body" v-else>
                    <p>Chargement des détails…</p>
                </div>
            </div>
        </div>
    </Teleport>

</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, computed, watch } from 'vue'
import caravaneImg from '@/assets/images/site/145.jpg'
import tableImg from '@/assets/images/site/20.jpg'
import desertImg from '@/assets/images/site/desert1.jpg'
import dcImg from '@/assets/images/site/dc.jpg'

const props = defineProps({
    displayMode: { type: String, default: 'carousel' },
    sectionTitle: { type: String, default: 'Un programme adapté pour tous les âges et envies:' },
    sectionSubtitle: { type: String, default: 'aventure, détente et découverte.' },
    showStats: { type: Boolean, default: true },
    customActivities: { type: Array, default: null }
})

const stats = ref([
    { number: '15+', label: 'Activités' },
    { number: '100%', label: 'Authentique' },
    { number: '24/7', label: 'Assistance' },
])

/* ========== DATA CHARGÉE DE L'API (activités réelles) ========== */
const API = '/api'
function getJson(data) {
    return data?.['hydra:member'] || data?.member || data || []
}
async function fetchJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' } })
    if (!res.ok) throw new Error(`${res.status} ${res.statusText}`)
    return await res.json()
}

const list = ref([])         // activités côté API
const loading = ref(true)

async function loadActivities() {
    loading.value = true
    try {
        const type = encodeURIComponent('App\\Models\\Activity')
        const data = await fetchJson(`${API}/products?type=${type}&status=active&per_page=12&mode=light`)        
        const items = getJson(data)
        list.value = items.map(a => ({
            id: a.id, // IMPORTANT pour la modale
            title: a.name,
            shortTitle: a.name?.split(' ')?.[0] || a.name,
            description: a.description,
            image: a.image || desertImg,
            icon: 'fas fa-hiking',
            duration: a?.productable_data?.duration
                ? `${a.productable_data.duration} min`
                : a?.productableDetail?.duration
                    ? `${a.productableDetail.duration} min`
                    : null,
            difficulty: a?.productable_data?.difficulty_level
                ? `Niveau ${a.productable_data.difficulty_level}`
                : a?.productableDetail?.difficulty_level
                    ? `Niveau ${a.productableDetail.difficulty_level}`
                    : null,
            features: [],
            price: a.price,
            formatted_price: a.formatted_price
        }))
    } finally {
        loading.value = false
    }
}

/* ========== FALLBACK LOCAL (comme avant) ========== */
const defaultActivities = [
    {
        title: 'Caravane chamelière',
        shortTitle: 'Caravane',
        description: 'Traversez les dunes dorées à dos de chameau au coucher du soleil',
        image: caravaneImg,
        icon: 'fas fa-horse-head',
        duration: '3-4 heures',
        difficulty: 'Facile',
        features: ['Guide expérimenté inclus', 'Thé traditionnel offert', 'Photos souvenirs', 'Transport aller-retour']
    },
    {
        title: 'Nuits sous les étoiles',
        shortTitle: 'Étoiles',
        description: 'Dormez sous un ciel parsemé d\'étoiles dans le silence du désert',
        image: dcImg,
        icon: 'fas fa-star',
        duration: 'Une nuit',
        difficulty: 'Facile',
        features: ['Campement traditionnel', 'Repas berbère authentique', 'Feu de camp et musique', 'Literie fournie']
    },
    {
        title: 'Randonnée sur dunes',
        shortTitle: 'Randonnée',
        description: 'Explorez les plus hautes dunes du Sahara avec nos guides experts',
        image: desertImg,
        icon: 'fas fa-hiking',
        duration: '2-6 heures',
        difficulty: 'Modéré',
        features: ['Plusieurs circuits disponibles', 'Eau et collations incluses', 'Guide naturaliste', 'Équipement de sécurité']
    },
    {
        title: 'Musique berbère',
        shortTitle: 'Musique',
        description: 'Soirées traditionnelles autour du feu avec musiciens locaux',
        image: caravaneImg,
        icon: 'fas fa-music',
        duration: '2-3 heures',
        difficulty: 'Facile',
        features: ['Musiciens traditionnels', 'Danse participative', 'Instruments à essayer', 'Thé à la menthe offert']
    },
    {
        title: 'Gastronomie authentique',
        shortTitle: 'Cuisine',
        description: 'Savourez la cuisine du désert préparée au feu de bois',
        image: tableImg,
        icon: 'fas fa-utensils',
        duration: '1-2 heures',
        difficulty: 'Facile',
        features: ['Ingrédients locaux frais', 'Recettes traditionnelles', 'Cuisson au feu de bois', 'Végétariens bienvenus']
    }
]

/* ========== SOURCE UNIQUE POUR LE TEMPLATE ========== */
const activities = computed(() => props.customActivities || (list.value.length ? list.value : defaultActivities))

/* ========== MOTEUR CARROUSEL (restauré) ========== */
const currentSlide = ref(0)
const isAutoplayPaused = ref(false)
let autoplayTimer = null

function nextSlide() {
    const len = activities.value.length
    if (!len) return
    currentSlide.value = (currentSlide.value + 1) % len
}
function prevSlide() {
    const len = activities.value.length
    if (!len) return
    currentSlide.value = currentSlide.value === 0 ? len - 1 : currentSlide.value - 1
}
function goToSlide(i) {
    const len = activities.value.length
    if (!len) return
    currentSlide.value = Math.max(0, Math.min(i, len - 1))
}
function startAutoplay() {
    stopAutoplay()
    if (activities.value.length <= 1) return
    autoplayTimer = setInterval(() => {
        if (!isAutoplayPaused.value) nextSlide()
    }, 4000)
}
function stopAutoplay() {
    if (autoplayTimer) {
        clearInterval(autoplayTimer)
        autoplayTimer = null
    }
}
function pauseAutoplay() { isAutoplayPaused.value = true }
function resumeAutoplay() { isAutoplayPaused.value = false }

/* ========== MODALE ACTIVITÉ (conservée) ========== */
const selectedActivity = ref(null)    // id de l’activité
const fullById = ref({})              // cache détails /api/products/{id}
const loadingById = ref({})

function closeModal() { selectedActivity.value = null }

function openActivity(activity) {
    const id = activity?.id
        ?? activity?.productId // garde-fou au cas où
    if (!id) return
    selectedActivity.value = id
    if (!fullById.value[id] && !loadingById.value[id]) loadActivityDetail(id)
}

async function loadActivityDetail(id) {
    loadingById.value[id] = true
    try {
        const data = await fetchJson(`${API}/products/${id}`)
        fullById.value[id] = data
    } catch (e) {
        // silencieux
    } finally {
        loadingById.value[id] = false
    }
}

const displayedActivity = computed(() => {
    const id = selectedActivity.value
    if (!id) return null
    return fullById.value[id]
        || activities.value.find(a => a.id === id)
        || null
})

function buildMeta(p = {}) {
    const detail = p?.productableDetail || p?.productable_data || {}
    const rows = []
    const dur = p.duration || (detail.duration ? `${detail.duration} min` : null)
    if (dur) rows.push(['Durée', dur])
    const diff = p.difficulty || (detail.difficulty_level ? `Niveau ${detail.difficulty_level}` : null)
    if (diff) rows.push(['Difficulté', diff])
    if (detail.max_people) rows.push(['Capacité', `${detail.max_people} pers.`])
    if (detail.meeting_point) rows.push(['Point de RDV', detail.meeting_point])
    return rows
}
const metaForSelected = computed(() => (displayedActivity.value ? buildMeta(displayedActivity.value) : []))

/* ========== LIFECYCLE ========== */
onMounted(async () => {
    await loadActivities()
    if (props.displayMode === 'carousel') startAutoplay()
})
onBeforeUnmount(stopAutoplay)

watch(() => props.displayMode, (mode) => {
    if (mode === 'carousel') startAutoplay()
    else stopAutoplay()
})

watch(activities, (arr) => {
    // relance l’autoplay quand la liste réelle arrive
    if (props.displayMode === 'carousel' && arr.length > 1) startAutoplay()
})
</script>
