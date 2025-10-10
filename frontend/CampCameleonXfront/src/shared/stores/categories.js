import { defineStore } from 'pinia'
import CategoriesApi from '@/services/CategoriesApi'

export const useCategoriesStore = defineStore('categories', {
  state: () => ({
    categories: [],
    loading: false,
    error: null
  }),

  getters: {
    categoriesByType: (state) => {
      const grouped = {}
      state.categories.forEach(category => {
        if (!grouped[category.type]) {
          grouped[category.type] = []
        }
        grouped[category.type].push(category)
      })
      return grouped
    },

    getCategoriesByType: (state) => (type) => {
      return state.categories.filter(cat => cat.type === type)
    },

    totalCategories: (state) => state.categories.length
  },

  actions: {
    async fetchCategories() {
      this.loading = true
      this.error = null
      
      try {
        this.categories = await CategoriesApi.getAll()
      } catch (error) {
        this.error = error.message
      } finally {
        this.loading = false
      }
    },

    async createCategory(categoryData) {
      try {
        const newCategory = await CategoriesApi.create(categoryData)
        this.categories.push(newCategory)
        return newCategory
      } catch (error) {
        this.error = error.message
        throw error
      }
    },

    async updateCategory(id, categoryData) {
      try {
        const updated = await CategoriesApi.update(id, categoryData)
        const index = this.categories.findIndex(cat => cat.id === id)
        if (index !== -1) {
          this.categories[index] = updated
        }
        return updated
      } catch (error) {
        this.error = error.message
        throw error
      }
    },

    async deleteCategory(id) {
      try {
        await CategoriesApi.delete(id)
        this.categories = this.categories.filter(cat => cat.id !== id)
      } catch (error) {
        this.error = error.message
        throw error
      }
    }
  }
})