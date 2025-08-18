class ProductUtils {

    /**
     * Configuration des types de produits
     */
    static getTypeConfig(type) {
        const configs = {
            activity: {
                label: 'Activités',
                singular: 'Activité',
                icon: 'fas fa-hiking',
                color: '#3b82f6',
                class: 'App\\Models\\Activity',
                hasRelation: false
            },
            menu: {
                label: 'Menus',
                singular: 'Menu',
                icon: 'fas fa-utensils',
                color: '#10b981',
                class: 'App\\Models\\Menu',
                hasRelation: 'dishes'
            },
            dish: {
                label: 'Plats',
                singular: 'Plat',
                icon: 'fas fa-drumstick-bite',
                color: '#f97316',
                class: 'App\\Models\\Dish',
                hasRelation: 'ingredients'
            },
            ingredient: {
                label: 'Ingrédients',
                singular: 'Ingrédient',
                icon: 'fas fa-seedling',
                color: '#22c55e',
                class: 'App\\Models\\Ingredient',
                hasRelation: false
            },
            room: {
                label: 'Hébergements',
                singular: 'Hébergement',
                icon: 'fas fa-bed',
                color: '#f59e0b',
                class: 'App\\Models\\Room',
                hasRelation: false
            }
        }

        return configs[type] || configs.activity
    }

    /**
     * Formater le prix
     */
    static formatPrice(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR'
        }).format(parseFloat(price) || 0)
    }

    /**
     * Formater la date
     */
    static formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }

        return new Date(date).toLocaleDateString('fr-FR', { ...defaultOptions, ...options })
    }

    /**
     * Obtenir l'URL d'image valide
     */
    static getValidImageUrl(imageUrl) {
        if (!imageUrl) {
            return '/images/placeholder-product.svg'
        }

        if (imageUrl.startsWith('http')) {
            return imageUrl
        }

        if (imageUrl.startsWith('/')) {
            return imageUrl
        }

        return `/storage/${imageUrl}`
    }
}
export default new ProductUtils()