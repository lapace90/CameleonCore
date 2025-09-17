<template>
  <section class="menus-section">
    <!-- En-tête de section -->
    <div class="menu-section-header">
      <h2 class="menu-section-title-front">Venez découvrir les saveurs du désert</h2>
      <p class="section-subtitle">
        Une cuisine authentique préparée avec amour au cœur du Sahara
      </p>
    </div>
    
    <div class="menu-container">
      <!-- Grille des menus -->
      <div class="menus-grid">
        <div v-for="(menu, index) in menus" :key="index" class="menu-card" :class="{ featured: menu.featured }"
          @click="selectMenu(menu.id)">
          <!-- Image du menu -->
          <div class="menu-image">
            <img :src="menu.image" :alt="menu.title" loading="lazy" />
            <div class="image-overlay">

              <div class="price-badge">
                <span class="price">{{ menu.formatted_price }}€</span>
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
                  <i v-for="n in 5" :key="n" :class="n <= menu.rating ? 'fas fa-star' : 'far fa-star'">
                  </i>
                </div>
                <span class="rating-text">({{ menu.reviews }})</span>
              </div>
            </div>

            <p class="menu-description">{{ menu.description }}</p>

            <!-- Plats inclus -->
            <!-- <div class="menu-items">
              <h4>Inclus dans ce menu :</h4>
              <ul class="items-list">
                <li v-for="item in menu.items" :key="item" class="menu-item">
                  <i class="fas fa-utensils"></i>
                  <span>{{ item }}</span>
                </li>
              </ul>
            </div> -->

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
                <span>{{ selectedMenu === menu.id ? 'Sélectionné' : 'En savoir plus' }}</span>
              </button>
            </div>
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
  
  </section>
  <!-- MODALE DÉTAIL MENU -->
  <Teleport to="body">
    <div v-if="selectedMenu" class="cc-modal-backdrop" @click.self="selectedMenu = null">
      <div class="cc-modal" role="dialog" aria-modal="true">

        <!-- Détails chargés -->
        <div class="cc-modal-body" v-if="fullById[selectedMenu]">
          <header class="cc-modal-head">
            <h3 class="cc-title">{{ fullById[selectedMenu].name }}</h3>
            <div class="cc-price">
              <span>
                {{ fullById[selectedMenu].formatted_price
                  || (fullById[selectedMenu].price + '€') }}
              </span>
              <small class="cc-unit">/ pers</small>
            </div>
          </header>

          <img class="cc-modal-image" :src="fullById[selectedMenu].image || '/images/placeholder-product.svg'"
            :alt="fullById[selectedMenu].name" loading="lazy" />

          <p class="cc-desc">
            {{ fullById[selectedMenu].description }}
          </p>

          <h4 class="cc-subtitle">Plats inclus</h4>
          <ul class="cc-items">
            <li v-for="n in dishNames(fullById[selectedMenu])" :key="n">
              <i class="fas fa-utensils"></i> {{ n }}
            </li>
          </ul>

          <div class="cc-actions">
            <button class="btn btn-primary btn-sm">
              <i class="fas fa-calendar-check "></i> Réserver
            </button>
            <button class="btn btn-outline btn-sm" @click="selectedMenu = null">
              Fermer
            </button>
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
import { onMounted, ref, computed } from 'vue'
import CouscousImg from '@/assets/images/site/couscous.jpg'

const API = '/api' // si tu n'as pas de proxy, mets 'http://localhost:8000/api'

const selectedMenu = ref(null)
const loadingList = ref(true)
const loadingIds = ref({}) // { [id]: true }
const baseMenus = ref([])  // liste de base (sans plats)
const fullById = ref({})   // détails { [id]: produit complet }

function getJson(data) {
  // API Platform (Hydra) ou tableau simple
  if (!data) return []
  return data['hydra:member'] || data.member || data
}

async function fetchJson(url) {
  const res = await fetch(url, { headers: { Accept: 'application/json' } })
  if (!res.ok) throw new Error(`${res.status} ${res.statusText}`)
  return await res.json()
}

function dishNames(p) {
  const raw =
    p?.productableDetail?.dishes ??
    p?.productable_detail?.dishes ??
    p?.relations?.dishes ?? []
  const arr = Array.isArray(raw) ? raw : Object.values(raw || {})
  return arr.map(d => d?.product?.name || d?.name).filter(Boolean)
}

async function loadMenusList() {
  loadingList.value = true
  try {
    const type = encodeURIComponent('App\\Models\\Menu')
    const data = await fetchJson(`${API}/products?type=${type}&status=active&per_page=12`)
    baseMenus.value = getJson(data)
  } finally {
    loadingList.value = false
  }
}

async function loadMenuDetail(id) {
  if (loadingIds.value[id] || fullById.value[id]) return
  loadingIds.value[id] = true
  try {
    const data = await fetchJson(`${API}/products/${id}`)
    fullById.value[id] = data
  } catch (e) {
    console.warn('menu detail', id, e.message)
  } finally {
    loadingIds.value[id] = false
  }
}

const menus = computed(() => {
  // map la liste → cartes UI, en injectant les noms si on a le détail
  return (baseMenus.value || []).map(p => {
    const full = fullById.value[p.id] || p
    return {
      id: p.id,
      title: p.name,
      description: p.description,
      price: p.price,
      image: p.image || '/images/placeholder-product.svg',
      rating: full?.statistics?.average_rating ?? 5,
      reviews: full?.statistics?.reviews ?? 0,
      popular: false,
      featured: false,
      items: dishNames(full) // ← “Inclus dans ce menu :”
    }
  })
})

// fallback local si l’API ne renvoie rien (inchangé)
const localMenus = [
  {
    id: 1,
    title: 'Menu Découverte',
    description: 'Une initiation parfaite aux saveurs du désert avec des plats traditionnels revisités.',
    price: 35, rating: 4, reviews: 127, popular: false, featured: false,
    image: 'https://images.unsplash.com/photo-1546833999-b9f581a1996d?auto=format&fit=crop&w=800&q=80',
    items: [
      "Soupe d'orge aux légumes du désert",
      'Tagine de légumes aux épices berbères',
      'Pain traditionnel cuit au sable',
      'Thé à la menthe et pâtisseries'
    ],
    dietary: ['Végétarien', 'Sans gluten']
  },
  {
    id: 2,
    title: 'Menu Authentique',
    description: "L'expérience culinaire complète avec les spécialités les plus appréciées de la région.",
    price: 45, rating: 5, reviews: 203, popular: true, featured: true,
    image: CouscousImg,
    items: [
      "Méchoui d'agneau aux herbes du désert",
      'Couscous royal aux sept légumes',
      'Chorba (soupe traditionnelle)',
      'Assortiment de dattes et fruits secs',
      'Thé à la menthe et cornes de gazelle'
    ],
    dietary: ['Halal', 'Traditionnel']
  }
]

const menusForTemplate = computed(() => {
  const arr = menus.value
  return (arr && arr.length) ? arr : localMenus
})

function selectMenu(id) {
  selectedMenu.value = selectedMenu.value === id ? null : id
  // charge les plats UNIQUEMENT si l’utilisateur s’intéresse au menu
  if (selectedMenu.value) loadMenuDetail(id)
}

onMounted(loadMenusList)
</script>

