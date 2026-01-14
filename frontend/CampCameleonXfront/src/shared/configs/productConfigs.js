/**
 * Configuration des types de produits - SOURCE UNIQUE
 * Remplace les duplications dans ProductForm.vue, ProductsApi.js, Breadcrumb.vue, ProductsShow.vue
 */

export const PRODUCT_CONFIGS = {
  ingredient: {
    label: 'Ingrédients',
    singular: 'Ingrédient',
    icon: 'fas fa-seedling',
    color: '#22c55e',
    fields: ['stock', 'is_vegetarian', 'is_vegan', 'is_spicy', 'is_gluten_free', 'is_lactose_free', 'is_nut_free'],
    hasRelation: 'dishes'
  },
  dish: {
    label: 'Plats',
    singular: 'Plat',
    icon: 'fas fa-drumstick-bite',
    color: '#f97316',
    fields: ['is_vegetarian', 'is_vegan', 'is_spicy', 'is_gluten_free', 'is_lactose_free', 'is_nut_free'],
    hasRelation: 'ingredients'
  },
  menu: {
    label: 'Menus',
    singular: 'Menu',
    icon: 'fas fa-utensils',
    color: '#10b981',
    fields: [],
    hasRelation: 'dishes'
  },
  room: {
    label: 'Hébergements',
    singular: 'Hébergement',
    icon: 'fas fa-bed',
    color: '#f59e0b',
    fields: ['capacity', 'availability'],
    hasRelation: false
  },
  activity: {
    label: 'Activités',
    singular: 'Activité',
    icon: 'fas fa-hiking',
    color: '#3b82f6',
    fields: ['guide', 'duration', 'meeting_point', 'max_people', 'difficulty_level'],
    hasRelation: false
  }
}

export const PRODUCTABLE_TYPE_MAP = {
  'activity': 'App\\Models\\Activity',
  'ingredient': 'App\\Models\\Ingredient',
  'dish': 'App\\Models\\Dish',
  'menu': 'App\\Models\\Menu',
  'room': 'App\\Models\\Room'
}

// Helper pour convertir type frontend → productableType backend
export function getProductableType(type) {
  return PRODUCTABLE_TYPE_MAP[type] || 'App\\Models\\Activity'
}

// Helper pour buildTypeConfig (compatibilité ProductsApi.js)
export function buildTypeConfigFromProductableType(productableType) {
  const configs = {
    'App\\Models\\Activity': PRODUCT_CONFIGS.activity,
    'App\\Models\\Menu': PRODUCT_CONFIGS.menu,
    'App\\Models\\Dish': PRODUCT_CONFIGS.dish,
    'App\\Models\\Ingredient': PRODUCT_CONFIGS.ingredient,
    'App\\Models\\Room': PRODUCT_CONFIGS.room
  }
  return configs[productableType] || PRODUCT_CONFIGS.activity
}