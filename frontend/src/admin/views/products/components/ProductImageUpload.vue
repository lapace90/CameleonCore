<template>
  <div class="form-left">
    <div class="image-upload-section">
      <h3>Image</h3>
      <div class="image-upload-area">
        <!-- Image existante -->
        <div v-if="displayUrl" class="main-image current-image">
          <img :src="displayUrl" :alt="'Image du produit'" @error="handleImageError" />
          <div class="image-overlay">
            <button type="button" @click="changeImage" class="overlay-btn">
              <AppIcon name="pencil" />
            </button>
            <button type="button" @click="removeImage" class="overlay-btn">
              <AppIcon name="trash-2" />
            </button>
          </div>
        </div>

        <!-- Placeholder d'upload -->
        <div v-else class="upload-placeholder" @click="selectImage">
          <AppIcon name="cloud-upload" />
          <p>Ajouter une image</p>
        </div>

        <!-- Input file caché -->
        <input ref="imageInput" type="file" accept="image/*" @change="handleImageUpload" style="display: none" />
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
      filePreview: null,               // object URL (blob:) utilisé pour l'aperçu
      _previewObjectUrl: null,         // pour revoke
      errorUrl: null,
      placeholderPath: 'https://via.placeholder.com/300x200?text=Image+indisponible'
    }
  },

  computed: {
    displayUrl() {
      // 1) preview local (blob:)
      if (this.filePreview) return this.filePreview

      // 2) si modelValue est une string, accepter http(s) / relative / blob: ; REFUSER data:
      if (typeof this.modelValue === 'string' && this.modelValue) {
        const s = this.modelValue
        if (s.startsWith('data:')) return null
        if (s.startsWith('blob:')) return s
        try {
          // si c'est une URL absolue valide, on la retourne
          const u = new URL(s, window.location.origin)
          return u.href
        } catch (e) {
          // chemin relatif (ex: /images/x.png) : on l'accepte
          if (/^[./]/.test(s) || !s.includes(':')) return s
        }
      }

      return null
    }
  },

  watch: {
    modelValue: {
      immediate: true,
      handler(newValue) {
        // Si on reçoit un File => crée preview (object URL)
        if (newValue instanceof File) {
          this.createFilePreview(newValue)
        } else if (typeof newValue === 'string') {
          // si c'est une URL string on supprime preview local
          this._revokePreview()
          this.filePreview = null
        } else if (newValue === null) {
          this._revokePreview()
          this.filePreview = null
          this.errorUrl = null
        }
      }
    }
  },

  methods: {
    // ouvre le selecteur
    selectImage() {
      this.$refs.imageInput?.click()
    },

    changeImage() {
      this.selectImage()
    },

    // handle upload (await validation)
    async handleImageUpload(event) {
      const file = event.target.files?.[0]
      if (!file) return

      // validation async
      try {
        const ok = await this.validateImageFile(file)
        if (!ok) return
      } catch (err) {
        // en cas d'erreur dans la validation
        console.error('Validation image error', err)
        return
      }

      // créer preview local et émettre le File vers le parent
      this.createFilePreview(file)
      this.$emit('update:modelValue', file)
    },

    // preview sans base64
    createFilePreview(file) {
      if (!file || !(file instanceof File)) return
      // révoque l'ancienne si existante
      this._revokePreview()
      try {
        const url = URL.createObjectURL(file)
        this._previewObjectUrl = url
        this.filePreview = url
      } catch (e) {
        console.error('Erreur createFilePreview', e)
      }
    },

    _revokePreview() {
      if (this._previewObjectUrl) {
        try { URL.revokeObjectURL(this._previewObjectUrl) } catch (e) { /* ignore */ }
        this._previewObjectUrl = null
      }
    },

    isErrorUrl(url) {
      return this.errorUrl === url
    },

    // remplace l'image par un placeholder HTTP (pas de base64 / pas de blob)
    handleImageError(event) {
      try {
        console.warn('🚨 Image error:', event.target.src)
        this.errorUrl = event.target.src
        event.target.onerror = null
        event.target.src = this.placeholderPath
      } catch (e) {
        console.error('handleImageError failed', e)
      }
    },

    // supprime l'image sélectionnée / URL
    removeImage() {
      this._revokePreview()
      this.filePreview = null
      this.errorUrl = null
      this.$emit('update:modelValue', null)
      if (this.$refs.imageInput) this.$refs.imageInput.value = ''
    },

    // validation asynchrone
    validateImageFile(file) {
      return new Promise((resolve) => {
        // taille max
        if (file.size > 5 * 1024 * 1024) {
          alert('Fichier trop volumineux (max 5MB)')
          return resolve(false)
        }

        // types MIME
        const allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp']
        if (!allowed.includes(file.type)) {
          alert('Format non supporté. Utilisez JPEG, PNG ou WebP')
          return resolve(false)
        }

        // dimensions via objectURL
        const img = new Image()
        const url = URL.createObjectURL(file)

        img.onload = () => {
          URL.revokeObjectURL(url)
          if (img.width > 4000 || img.height > 4000) {
            alert('Image trop grande (max 4000x4000px)')
            return resolve(false)
          }
          return resolve(true)
        }

        img.onerror = () => {
          URL.revokeObjectURL(url)
          alert('Fichier image corrompu ou invalide')
          return resolve(false)
        }

        img.src = url
      })
    },

    getPlaceholderImage() {
      return this.placeholderPath
    }
  },

  beforeUnmount() {
    this._revokePreview()
  }
}
</script>
