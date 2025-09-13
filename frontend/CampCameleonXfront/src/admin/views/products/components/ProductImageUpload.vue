<template>
  <div class="form-left">
    <div class="image-upload-section">
      <h3>Image</h3>
      <div class="image-upload-area">
        <!-- Image existante ou preview -->
        <div v-if="imagePreview || modelValue" class="main-image current-image">
          <img 
            :src="getValidImageUrl(imagePreview || modelValue)" 
            :alt="'Image du produit'"
            @error="handleImageError" 
          />
          <div class="image-overlay">
            <button type="button" @click="changeImage" class="overlay-btn">
              <i class="fas fa-edit"></i>
            </button>
            <button type="button" @click="removeImage" class="overlay-btn">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        
        <!-- Placeholder d'upload -->
        <div v-else class="upload-placeholder" @click="selectImage">
          <i class="fas fa-cloud-upload-alt"></i>
          <p>Ajouter une image</p>
        </div>
        
        <!-- Input file caché -->
        <input 
          ref="imageInput" 
          type="file" 
          accept="image/*" 
          @change="handleImageUpload"
          style="display: none" 
        />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProductImageUpload',
  props: {
    modelValue: {
      type: [String, File],
      default: null
    }
  },

  emits: ['update:modelValue'],

  data() {
    return {
      imagePreview: null
    }
  },

  methods: {
    getValidImageUrl(url) {
      if (!url) return this.getPlaceholderImage()
      
      // Si c'est un fichier File, utiliser imagePreview
      if (url instanceof File) return this.imagePreview
      
      // Si c'est une URL, la retourner
      return url
    },

    getPlaceholderImage() {
      const svg = `
        <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
          <rect width="300" height="200" fill="#f3f4f6"/>
          <text x="150" y="100" text-anchor="middle" dy=".3em" font-family="Arial, sans-serif" font-size="14" fill="#9ca3af">
            Aperçu
          </text>
        </svg>
      `
      return 'data:image/svg+xml;base64,' + btoa(svg)
    },

    handleImageError(event) {
      event.target.src = this.getPlaceholderImage()
      event.target.onerror = null
    },

    selectImage() {
      this.$refs.imageInput.click()
    },

    handleImageUpload(event) {
      const file = event.target.files[0]
      if (!file) return

      // Vérifier la taille (max 5MB)
      if (file.size > 5 * 1024 * 1024) {
        alert('Fichier trop volumineux (max 5MB)')
        return
      }

      // Créer le preview
      this.imagePreview = URL.createObjectURL(file)
      
      // Émettre le fichier vers le parent
      this.$emit('update:modelValue', file)
    },

    changeImage() {
      this.selectImage()
    },

    removeImage() {
      this.imagePreview = null
      this.$emit('update:modelValue', null)
      
      // Reset de l'input file
      if (this.$refs.imageInput) {
        this.$refs.imageInput.value = ''
      }
    }
  }
}
</script>