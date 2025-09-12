<!-- src/shared/components/ui/ImageUpload.vue -->
<template>
  <div class="image-upload">
    <!-- Zone de prévisualisation -->
    <div :class="previewClasses" @click="openFilePicker">
      <div v-if="previewUrl || modelValue" class="preview-image">
        <img :src="previewUrl || modelValue" :alt="alt" />
        <div class="preview-overlay">
          <i class="fas fa-camera"></i>
          <span>Changer</span>
        </div>
      </div>
      
      <div v-else class="preview-placeholder">
        <i :class="placeholderIcon"></i>
        <span class="placeholder-text">{{ placeholderText }}</span>
        <small class="placeholder-hint">Cliquez pour ajouter une image</small>
      </div>
    </div>
    
    <!-- Input file caché -->
    <input
      ref="fileInput"
      type="file"
      :accept="acceptedTypes"
      @change="handleFileSelect"
      style="display: none;"
    />
    
    <!-- Actions -->
    <div class="upload-actions">
      <BaseButton
        variant="outline"
        size="sm"
        icon="fas fa-upload"
        @click="openFilePicker"
      >
        {{ modelValue ? 'Changer' : 'Ajouter' }}
      </BaseButton>
      
      <BaseButton
        v-if="modelValue && allowRemove"
        variant="danger"
        size="sm"
        icon="fas fa-trash"
        @click="removeImage"
      >
        Supprimer
      </BaseButton>
    </div>
    
    <!-- Informations et erreurs -->
    <div v-if="error" class="upload-error">
      <i class="fas fa-exclamation-triangle"></i>
      {{ error }}
    </div>
    
    <div v-if="help" class="upload-help">
      {{ help }}
    </div>
    
    <!-- Progress bar pour l'upload -->
    <div v-if="uploading" class="upload-progress">
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
      </div>
      <span class="progress-text">{{ uploadProgress }}% - Upload en cours...</span>
    </div>
  </div>
</template>

<script>
import BaseButton from '@/shared/components/ui/BaseButton.vue'

export default {
  name: 'ImageUpload',
  
  components: {
    BaseButton
  },
  
  emits: ['update:modelValue', 'file-selected', 'upload-start', 'upload-success', 'upload-error'],
  
  props: {
    modelValue: String, // URL de l'image actuelle
    shape: {
      type: String,
      default: 'square',
      validator: v => ['square', 'circle', 'rectangle'].includes(v)
    },
    size: {
      type: String,
      default: 'md',
      validator: v => ['sm', 'md', 'lg', 'xl'].includes(v)
    },
    acceptedTypes: {
      type: String,
      default: 'image/*'
    },
    maxSize: {
      type: Number,
      default: 5 * 1024 * 1024 // 5MB par défaut
    },
    placeholderIcon: {
      type: String,
      default: 'fas fa-image'
    },
    placeholderText: {
      type: String,
      default: 'Aucune image'
    },
    alt: {
      type: String,
      default: 'Image'
    },
    allowRemove: {
      type: Boolean,
      default: true
    },
    autoUpload: {
      type: Boolean,
      default: false
    },
    uploadUrl: String,
    help: String,
    error: String
  },
  
  data() {
    return {
      previewUrl: null,
      selectedFile: null,
      uploading: false,
      uploadProgress: 0
    }
  },
  
  computed: {
    previewClasses() {
      return [
        'image-preview',
        `preview-${this.shape}`,
        `preview-${this.size}`,
        {
          'preview-empty': !this.previewUrl && !this.modelValue,
          'preview-hover': true
        }
      ]
    }
  },
  
  methods: {
    openFilePicker() {
      this.$refs.fileInput.click()
    },
    
    handleFileSelect(event) {
      const file = event.target.files[0]
      if (!file) return
      
      // Validation du type de fichier
      if (!file.type.startsWith('image/')) {
        this.$emit('upload-error', 'Veuillez sélectionner une image valide')
        return
      }
      
      // Validation de la taille
      if (file.size > this.maxSize) {
        const maxSizeMB = Math.round(this.maxSize / (1024 * 1024))
        this.$emit('upload-error', `L'image ne doit pas dépasser ${maxSizeMB}MB`)
        return
      }
      
      this.selectedFile = file
      
      // Créer la prévisualisation
      const reader = new FileReader()
      reader.onload = (e) => {
        this.previewUrl = e.target.result
      }
      reader.readAsDataURL(file)
      
      this.$emit('file-selected', file)
      
      // Auto-upload si activé
      if (this.autoUpload && this.uploadUrl) {
        this.uploadFile()
      } else {
        // Émettre l'URL de prévisualisation
        this.$emit('update:modelValue', this.previewUrl)
      }
    },
    
    async uploadFile() {
      if (!this.selectedFile || !this.uploadUrl) return
      
      this.uploading = true
      this.uploadProgress = 0
      this.$emit('upload-start')
      
      try {
        const formData = new FormData()
        formData.append('image', this.selectedFile)
        
        // Simulation d'upload avec progress
        const response = await fetch(this.uploadUrl, {
          method: 'POST',
          body: formData
        })
        
        if (!response.ok) {
          throw new Error('Erreur lors de l\'upload')
        }
        
        const result = await response.json()
        this.uploadProgress = 100
        
        // Émettre l'URL finale
        this.$emit('update:modelValue', result.url)
        this.$emit('upload-success', result)
        
      } catch (error) {
        this.$emit('upload-error', error.message)
      } finally {
        this.uploading = false
      }
    },
    
    removeImage() {
      this.previewUrl = null
      this.selectedFile = null
      this.$emit('update:modelValue', null)
      
      // Reset input file
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    }
  }
}
</script>

<style scoped>
.image-upload {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

/* Prévisualisation */
.image-preview {
  position: relative;
  cursor: pointer;
  border: 2px dashed #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
  transition: all 0.2s ease;
}

.image-preview:hover {
  border-color: var(--primary);
}

/* Formes */
.preview-circle {
  border-radius: 50%;
}

.preview-rectangle {
  aspect-ratio: 16/9;
}

.preview-square {
  aspect-ratio: 1;
}

/* Tailles */
.preview-sm {
  width: 80px;
}

.preview-md {
  width: 120px;
}

.preview-lg {
  width: 160px;
}

.preview-xl {
  width: 200px;
}

/* Image */
.preview-image {
  position: relative;
  width: 100%;
  height: 100%;
}

.preview-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.preview-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.2s ease;
  gap: 0.25rem;
}

.preview-image:hover .preview-overlay {
  opacity: 1;
}

.preview-overlay i {
  font-size: 1.5rem;
}

.preview-overlay span {
  font-size: 0.875rem;
  font-weight: 500;
}

/* Placeholder */
.preview-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  color: #9ca3af;
  text-align: center;
  padding: 1rem;
  min-height: 120px;
}

.preview-placeholder i {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.placeholder-text {
  font-weight: 500;
  margin-bottom: 0.25rem;
}

.placeholder-hint {
  font-size: 0.75rem;
  color: #6b7280;
}

/* Actions */
.upload-actions {
  display: flex;
  gap: 0.5rem;
}

/* Messages */
.upload-error {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.875rem;
  color: var(--danger);
}

.upload-help {
  font-size: 0.875rem;
  color: #6b7280;
  text-align: center;
}

/* Progress bar */
.upload-progress {
  width: 100%;
  text-align: center;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: var(--primary);
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.875rem;
  color: #6b7280;
}
</style>