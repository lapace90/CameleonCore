<template>
  <div class="universal-image-upload" :class="[`shape-${shape}`, `size-${size}`, `variant-${variant}`]">
    <!-- Image existante ou preview -->
    <div v-if="displayUrl" class="image-container" @click="enableEditing && openFilePicker()">
      <img :src="displayUrl" :alt="alt" @error="handleImageError" />
      <div v-if="enableEditing" class="image-overlay">
        <button type="button" @click.stop="openFilePicker" class="overlay-btn edit-btn">
          <AppIcon name="pencil" />
          <span v-if="variant === 'product'">Modifier</span>
          <span v-else>Changer</span>
        </button>
        <button v-if="allowRemove" type="button" @click.stop="removeImage" class="overlay-btn remove-btn">
          <AppIcon name="trash-2" />
          <span v-if="variant === 'product'">Supprimer</span>
        </button>
      </div>
    </div>

    <!-- Placeholder d'upload -->
    <div v-else class="upload-placeholder" @click="enableEditing && openFilePicker()">
      <AppIcon :name="placeholderIcon" />
      <div class="placeholder-content">
        <p class="placeholder-text">{{ placeholderText }}</p>
        <small v-if="enableEditing" class="placeholder-hint">{{ placeholderHint }}</small>
      </div>
    </div>

    <!-- Input file caché -->
    <input 
      ref="fileInput" 
      type="file" 
      :accept="acceptedTypes" 
      @change="handleFileSelect" 
      style="display: none" 
    />

    <!-- Actions externes (pour variant profile) -->
    <div v-if="variant === 'profile' && showActions" class="external-actions">
      <button 
        type="button" 
        class="action-btn primary"
        @click="openFilePicker"
        :disabled="!enableEditing"
      >
        <AppIcon name="upload" />
        {{ displayUrl ? 'Changer' : 'Ajouter' }}
      </button>
      
      <button 
        v-if="displayUrl && allowRemove" 
        type="button" 
        class="action-btn danger"
        @click="removeImage"
        :disabled="!enableEditing"
      >
        <AppIcon name="trash-2" />
        Supprimer
      </button>
    </div>

    <!-- Messages d'aide et d'erreur -->
    <div v-if="help && variant === 'profile'" class="upload-help">
      {{ help }}
    </div>
    
    <div v-if="error" class="upload-error">
      <AppIcon name="triangle-alert" />
      {{ error }}
    </div>

    <!-- Indicateur de chargement -->
    <div v-if="uploading" class="upload-loading">
      <div class="loading-spinner"></div>
      <span>Upload en cours...</span>
    </div>
  </div>
</template>

<script>
import ProductsApi from '@/services/ProductsApi'

export default {
  name: 'ImageUpload',
  
  emits: ['update:modelValue', 'upload-start', 'upload-success', 'upload-error'],
  
  props: {
    modelValue: {
      type: [String, File],
      default: null
    },
    
    // Apparence
    variant: {
      type: String,
      default: 'product', // 'product' | 'profile'
      validator: v => ['product', 'profile'].includes(v)
    },
    shape: {
      type: String,
      default: 'rectangle', // 'rectangle' | 'square' | 'circle'
      validator: v => ['rectangle', 'square', 'circle'].includes(v)
    },
    size: {
      type: String,
      default: 'md', // 'sm' | 'md' | 'lg' | 'xl'
      validator: v => ['sm', 'md', 'lg', 'xl'].includes(v)
    },
    
    // Comportement
    enableEditing: {
      type: Boolean,
      default: true
    },
    allowRemove: {
      type: Boolean,
      default: true
    },
    showActions: {
      type: Boolean,
      default: true
    },
    
    // Validation
    acceptedTypes: {
      type: String,
      default: 'image/*'
    },
    maxSize: {
      type: Number,
      default: 5 * 1024 * 1024 // 5MB
    },
    
    // Textes
    placeholderIcon: {
      type: String,
      default: 'cloud-upload'
    },
    placeholderText: {
      type: String,
      default: 'Ajouter une image'
    },
    placeholderHint: {
      type: String,
      default: 'Cliquez pour ajouter une image'
    },
    alt: {
      type: String,
      default: 'Image uploadée'
    },
    help: {
      type: String,
      default: null
    }
  },
  
  data() {
    return {
      uploading: false,
      error: null,
      previewUrl: null
    }
  },
  
  computed: {
    displayUrl() {
      // Si on a un fichier en cours de sélection, montrer le preview
      if (this.previewUrl) return this.previewUrl
      
      // Si on a une URL string (image sauvegardée), la montrer
      if (typeof this.modelValue === 'string' && this.modelValue) {
        return this.modelValue
      }
      
      return null
    }
  },
  
  watch: {
    modelValue: {
      handler() {
        // Reset preview si la valeur change de l'extérieur
        if (typeof this.modelValue === 'string') {
          this.previewUrl = null
        }
      },
      immediate: true
    }
  },
  
  methods: {
    openFilePicker() {
      if (!this.enableEditing) return
      this.$refs.fileInput?.click()
    },
    
    async handleFileSelect(event) {
      const file = event.target.files[0]
      if (!file) return

      this.error = null

      // Validation
      if (!this.validateFile(file)) return

      try {
        this.uploading = true
        this.$emit('upload-start')

        // Créer preview immédiat
        this.createPreview(file)

        // Upload vers MediaObject (comme ProductImageUpload.vue)
        console.log('📤 Upload vers /api/media_objects...')
        const mediaResponse = await ProductsApi.uploadToMediaObjects(file)
        
        // Émettre l'URL finale
        const imageUrl = mediaResponse.contentUrl
        console.log('✅ MediaObject créé, URL assignée:', imageUrl)
        
        // Nettoyer le preview et émettre l'URL
        this.previewUrl = null
        this.$emit('update:modelValue', imageUrl)
        this.$emit('upload-success', { url: imageUrl })

      } catch (error) {
        console.error('Erreur upload:', error)
        this.error = 'Erreur lors de l\'upload de l\'image'
        this.previewUrl = null
        this.$emit('upload-error', error.message)
      } finally {
        this.uploading = false
      }
    },
    
    validateFile(file) {
      // Vérifier le type
      if (!file.type.startsWith('image/')) {
        this.error = 'Veuillez sélectionner une image'
        return false
      }
      
      // Vérifier la taille
      if (file.size > this.maxSize) {
        const maxMB = Math.round(this.maxSize / (1024 * 1024))
        this.error = `Fichier trop volumineux (max ${maxMB}MB)`
        return false
      }
      
      return true
    },
    
    createPreview(file) {
      const reader = new FileReader()
      reader.onload = (e) => {
        this.previewUrl = e.target.result
      }
      reader.readAsDataURL(file)
    },
    
    removeImage() {
      this.previewUrl = null
      this.error = null
      this.$emit('update:modelValue', null)
      
      // Reset input file
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },
    
    handleImageError(event) {
      // Placeholder SVG simple
      const svg = `
        <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
          <rect width="300" height="200" fill="#f3f4f6"/>
          <text x="150" y="100" text-anchor="middle" dy=".3em" 
                font-family="Arial, sans-serif" font-size="14" fill="#9ca3af">
            Image non disponible
          </text>
        </svg>
      `
      event.target.onerror = null
    }
  }
}
</script>

<style lang="scss" scoped>
.universal-image-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  
  // === SHAPES ===
  &.shape-rectangle .image-container,
  &.shape-rectangle .upload-placeholder {
    aspect-ratio: 4/3;
  }
  
  &.shape-square .image-container,
  &.shape-square .upload-placeholder {
    aspect-ratio: 1;
  }
  
  &.shape-circle .image-container,
  &.shape-circle .upload-placeholder {
    aspect-ratio: 1;
    border-radius: 50%;
  }
  
  // === SIZES ===
  &.size-sm .image-container,
  &.size-sm .upload-placeholder {
    width: 80px;
  }
  
  &.size-md .image-container,
  &.size-md .upload-placeholder {
    width: 120px;
  }
  
  &.size-lg .image-container,
  &.size-lg .upload-placeholder {
    width: 160px;
  }
  
  &.size-xl .image-container,
  &.size-xl .upload-placeholder {
    width: 200px;
  }
  
  // === VARIANT PRODUCT (style ProductImageUpload) ===
  &.variant-product {
    width: 100%;
    
    .image-container,
    .upload-placeholder {
      width: 100%;
      border: 2px dashed #d1d5db;
      border-radius: 8px;
      overflow: hidden;
      transition: border-color 0.2s ease;
      cursor: pointer;
      
      &:hover {
        border-color: #3b82f6;
      }
    }
    
    .upload-placeholder {
      background: #f9fafb;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #6b7280;
      padding: 2rem;
      
      .app-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
      }
      
      .placeholder-text {
        font-weight: 500;
        margin: 0 0 0.5rem 0;
      }
      
      .placeholder-hint {
        font-size: 0.875rem;
        opacity: 0.8;
      }
      
      &:hover {
        color: #3b82f6;
      }
    }
  }
  
  // === VARIANT PROFILE (style ImageUpload) ===
  &.variant-profile {
    .image-container,
    .upload-placeholder {
      border: 2px dashed #d1d5db;
      border-radius: 0.5rem;
      overflow: hidden;
      transition: all 0.2s ease;
      cursor: pointer;
      
      &:hover {
        border-color: #3b82f6;
      }
    }
    
    .upload-placeholder {
      background: #f9fafb;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: #9ca3af;
      padding: 1.5rem;
      text-align: center;
      min-height: 120px;
      
      .app-icon {
        font-size: 2rem;
        margin-bottom: 0.75rem;
      }
      
      .placeholder-content {
        .placeholder-text {
          font-weight: 500;
          margin: 0 0 0.25rem 0;
          color: #6b7280;
        }
        
        .placeholder-hint {
          font-size: 0.75rem;
          color: #9ca3af;
        }
      }
    }
  }
  
  // === IMAGE CONTAINER ===
  .image-container {
    position: relative;
    
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    
    .image-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      opacity: 0;
      transition: opacity 0.3s ease;
      
      .overlay-btn {
        background: white;
        border: none;
        border-radius: 6px;
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.875rem;
        transition: transform 0.2s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        
        &:hover {
          transform: scale(1.05);
        }
        
        &.remove-btn {
          background: #ef4444;
          color: white;
        }
      }
    }
    
    &:hover .image-overlay {
      opacity: 1;
    }
  }
  
  // === ACTIONS EXTERNES (profile variant) ===
  .external-actions {
    display: flex;
    gap: 0.5rem;
    
    .action-btn {
      border: none;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      cursor: pointer;
      font-size: 0.875rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.2s ease;
      
      &:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }
      
      &.primary {
        background: #3b82f6;
        color: white;
        
        &:hover:not(:disabled) {
          background: #2563eb;
        }
      }
      
      &.danger {
        background: #ef4444;
        color: white;
        
        &:hover:not(:disabled) {
          background: #dc2626;
        }
      }
    }
  }
  
  // === MESSAGES ===
  .upload-help {
    font-size: 0.875rem;
    color: #6b7280;
    text-align: center;
  }
  
  .upload-error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #ef4444;
    
    .app-icon {
      flex-shrink: 0;
    }
  }
  
  // === LOADING ===
  .upload-loading {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #3b82f6;
    
    .loading-spinner {
      width: 16px;
      height: 16px;
      border: 2px solid #e5e7eb;
      border-top: 2px solid #3b82f6;
      border-radius: 50%;
      animation: spin 1s linear infinite;
    }
  }
  
  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }
}
</style>