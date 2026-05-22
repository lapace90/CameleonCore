// frontend/CampCameleonXfront/src/shared/composables/useFormDraft.js

import { ref, watch, onMounted, onUnmounted } from 'vue'
import { storage } from '@/shared/utils/helpers'
import { debounce } from '@/shared/utils/helpers'

/**
 * Composable pour gérer les brouillons de formulaires
 * 
 * @param {string} formKey - Clé unique pour identifier le formulaire (ex: 'reservation-form', 'product-form-edit-123')
 * @param {Object} formData - Objet réactif contenant les données du formulaire
 * @param {Object} options - Options de configuration
 * @returns {Object} - Méthodes pour gérer les brouillons
 */
export function useFormDraft(formKey, formData, options = {}) {
  const {
    autoSaveDelay = 2000,        // Délai d'auto-save en ms (2 secondes par défaut)
    excludeFields = [],           // Champs à exclure de la sauvegarde
    onRestore = null,             // Callback appelé après restauration
    onSave = null,                // Callback appelé après sauvegarde
    ttl = 7 * 24 * 60 * 60 * 1000 // Durée de vie du brouillon (7 jours par défaut)
  } = options

  const STORAGE_PREFIX = 'draft_'
  const storageKey = `${STORAGE_PREFIX}${formKey}`
  
  const hasDraft = ref(false)
  const lastSaved = ref(null)
  const isSaving = ref(false)

  /**
   * Sauvegarde le brouillon dans le localStorage
   */
  const saveDraft = () => {
    try {
      isSaving.value = true
      
      // Filtrer les champs exclus
      const dataToSave = Object.keys(formData).reduce((acc, key) => {
        if (!excludeFields.includes(key)) {
          acc[key] = formData[key]
        }
        return acc
      }, {})

      const draft = {
        data: dataToSave,
        timestamp: Date.now(),
        formKey
      }

      storage.set(storageKey, draft)
      hasDraft.value = true
      lastSaved.value = new Date()

      console.log(`💾 Brouillon sauvegardé: ${formKey}`, {
        timestamp: new Date(draft.timestamp).toLocaleString(),
        fieldsCount: Object.keys(dataToSave).length
      })

      if (onSave) {
        onSave(draft)
      }
    } catch (error) {
      console.error('❌ Erreur lors de la sauvegarde du brouillon:', error)
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Restaure le brouillon depuis le localStorage
   */
  const restoreDraft = () => {
    try {
      const draft = storage.get(storageKey)

      if (!draft) {
        console.log(`ℹ️ Aucun brouillon trouvé pour: ${formKey}`)
        return false
      }

      // Vérifier si le brouillon n'a pas expiré
      const age = Date.now() - draft.timestamp
      if (age > ttl) {
        console.log(`⏰ Brouillon expiré (${Math.round(age / 1000 / 60 / 60 / 24)} jours)`)
        clearDraft()
        return false
      }

      // Restaurer les données
      Object.assign(formData, draft.data)
      hasDraft.value = true
      lastSaved.value = new Date(draft.timestamp)

      console.log(`✅ Brouillon restauré: ${formKey}`, {
        timestamp: new Date(draft.timestamp).toLocaleString(),
        age: `${Math.round(age / 1000 / 60)} minutes`
      })

      if (onRestore) {
        onRestore(draft)
      }

      return true
    } catch (error) {
      console.error('❌ Erreur lors de la restauration du brouillon:', error)
      return false
    }
  }

  /**
   * Supprime le brouillon du localStorage
   */
  const clearDraft = () => {
    try {
      storage.remove(storageKey)
      hasDraft.value = false
      lastSaved.value = null
      console.log(`🗑️ Brouillon supprimé: ${formKey}`)
    } catch (error) {
      console.error('❌ Erreur lors de la suppression du brouillon:', error)
    }
  }

  /**
   * Vérifie si un brouillon existe
   */
  const checkDraft = () => {
    const draft = storage.get(storageKey)
    if (draft) {
      const age = Date.now() - draft.timestamp
      if (age <= ttl) {
        hasDraft.value = true
        lastSaved.value = new Date(draft.timestamp)
        return true
      } else {
        clearDraft()
      }
    }
    return false
  }

  /**
   * Obtient les informations du brouillon sans le restaurer
   */
  const getDraftInfo = () => {
    const draft = storage.get(storageKey)
    if (!draft) return null

    const age = Date.now() - draft.timestamp
    if (age > ttl) {
      clearDraft()
      return null
    }

    return {
      timestamp: draft.timestamp,
      age,
      formKey: draft.formKey,
      fieldsCount: Object.keys(draft.data || {}).length
    }
  }

  // Auto-save avec debounce
  const debouncedSave = debounce(saveDraft, autoSaveDelay)

  // Watcher pour l'auto-save
  const stopWatcher = watch(
    () => JSON.stringify(formData),
    () => {
      debouncedSave()
    },
    { deep: true }
  )

  // Vérifier au montage si un brouillon existe
  onMounted(() => {
    checkDraft()
  })

  // Nettoyage au démontage
  onUnmounted(() => {
    stopWatcher()
  })

  // Sauvegarder avant de quitter la page
  const handleBeforeUnload = (e) => {
    if (hasDraft.value || Object.keys(formData).some(key => formData[key])) {
      saveDraft()
    }
  }

  // Ajouter l'écouteur d'événement
  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', handleBeforeUnload)
    
    onUnmounted(() => {
      window.removeEventListener('beforeunload', handleBeforeUnload)
    })
  }

  return {
    // État
    hasDraft,
    lastSaved,
    isSaving,

    // Méthodes
    saveDraft,
    restoreDraft,
    clearDraft,
    checkDraft,
    getDraftInfo
  }
}

/**
 * Utilitaire pour nettoyer tous les brouillons expirés
 */
export function cleanExpiredDrafts(ttl = 7 * 24 * 60 * 60 * 1000) {
  try {
    const keys = Object.keys(localStorage).filter(key => key.startsWith('draft_'))
    let cleanedCount = 0

    keys.forEach(key => {
      const draft = storage.get(key)
      if (draft && draft.timestamp) {
        const age = Date.now() - draft.timestamp
        if (age > ttl) {
          storage.remove(key)
          cleanedCount++
        }
      }
    })

    console.log(`🧹 Brouillons expirés nettoyés: ${cleanedCount}`)
    return cleanedCount
  } catch (error) {
    console.error('❌ Erreur lors du nettoyage des brouillons:', error)
    return 0
  }
}

/**
 * Utilitaire pour lister tous les brouillons disponibles
 */
export function listAllDrafts() {
  try {
    const keys = Object.keys(localStorage).filter(key => key.startsWith('draft_'))
    return keys.map(key => {
      const draft = storage.get(key)
      if (draft) {
        return {
          key,
          formKey: draft.formKey,
          timestamp: draft.timestamp,
          age: Date.now() - draft.timestamp,
          fieldsCount: Object.keys(draft.data || {}).length
        }
      }
      return null
    }).filter(Boolean)
  } catch (error) {
    console.error('❌ Erreur lors du listage des brouillons:', error)
    return []
  }
}