import { defineStore } from 'pinia'
import ProductsApi from '@/services/ProductsApi'

// Normalisation minimale des produits
function normalize(p = {}) {
  const out = { ...p }
  out.typeConfig        = out.typeConfig ?? out.type_config ?? null
  out.productableDetail = out.productableDetail ?? out.productable_detail ?? out.productable_data ?? null
  out.productableData   = out.productableData ?? out.productableDetail
  return out
}

/**
 * Store "produits" avec gestion de la pagination et des filtres.
 * Les produits peuvent être de différents types (activités, menus, etc.)
 * et avoir des structures différentes selon leur type.
 * Le store gère un tableau normalisé et un index rapide par ID.
 * Il fournit aussi des getters pour filtrer par type.
 * Le store gère aussi l'état de chargement, les erreurs, et la pagination.
 * Il mémorise aussi les derniers paramètres de filtre utilisés.
 */
export const useProductsStore = defineStore('products', {
  state: () => ({
    items: [],            // tableau normalisé
    mapById: {},          // index rapide par id
    loading: false,
    loaded: false,
    error: null,
    // pagination / vue courante
    totalItems: 0,
    currentPage: 1,
    lastPage: 1,
    // dernier filtre utilisé
    lastParams: {}
  }),

  getters: {
    // Helpers de filtrage par type
    activities: (s) =>
      s.items.filter(p =>
        (p.typeConfig?.label || p.type_config?.label) === 'Activités' ||
        p.productableType === 'App\\Models\\Activity'
      ),
    menus: (s) =>
      s.items.filter(p =>
        (p.typeConfig?.label || p.type_config?.label) === 'Menus' ||
        p.productableType === 'App\\Models\\Menu'
      ),
    byId: (s) => (id) => s.mapById[id] || null
  },

  actions: {
    async fetchAll(params = {}) {
      if (this.loading) return
      this.loading = true
      this.error = null
      this.lastParams = { ...params }

      try {
        const res = await ProductsApi.getProducts(params)
       
        const raw = res?.data ?? []
        const list = raw.map(normalize)

        this.items = list
        this.mapById = Object.fromEntries(list.map(p => [p.id, p]))
        this.totalItems = res?.totalItems ?? list.length
        this.currentPage = res?.currentPage ?? 1
        this.lastPage = res?.lastPage ?? 1
        this.loaded = true
      } catch (e) {
        this.error = e?.message || 'Erreur chargement produits'
        this.loaded = false
      } finally {
        this.loading = false
      }
    },

    async fetchById(id) {
      this.error = null
      try {
        const p = await ProductsApi.getProduct(id)
        const n = normalize(p)
        // merge dans le store
        const idx = this.items.findIndex(x => x.id === n.id)
        if (idx >= 0) this.items[idx] = n
        else this.items.push(n)
        this.mapById[n.id] = n
        return n
      } catch (e) {
        this.error = e?.message || `Erreur chargement produit ${id}`
        throw e
      }
    },
  }
})
