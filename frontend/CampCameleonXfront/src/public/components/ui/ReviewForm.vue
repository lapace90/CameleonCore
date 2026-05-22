<!-- frontend/CampCameleonXfront/src/public/components/ui/ReviewForm.vue -->
<template>
    <div class="form-container">
        <!-- Message de succès -->
        <div v-if="showSuccess" class="success-banner">
            <AppIcon name="circle-check" />
            <div>
                <strong>Merci pour votre avis !</strong>
                <p>Votre témoignage a bien été envoyé et sera publié après validation.</p>
            </div>
        </div>

        <!-- Formulaire utilisant les mêmes classes que ContactForm -->
        <form v-else @submit.prevent="submitReview" class="contact-form">
            <div class="form-row">
                <div class="contact-form-group">
                    <label for="client_name">Votre nom *</label>
                    <input type="text" id="client_name" v-model="form.client_name"
                        :class="{ 'error': errors.client_name }" placeholder="Jean Dupont" required>
                    <span v-if="errors.client_name" class="error-message">{{ errors.client_name }}</span>
                </div>

                <div class="contact-form-group">
                    <label for="location">Localisation</label>
                    <input type="text" id="location" v-model="form.location" placeholder="Paris, France">
                </div>
            </div>

            <div class="contact-form-group">
                <label for="email">Votre email *</label>
                <input type="email" id="email" v-model="form.email" :class="{ 'error': errors.email }"
                    placeholder="jean.dupont@example.com" required>
                <span v-if="errors.email" class="error-message">{{ errors.email }}</span>
            </div>

            <div class="contact-form-group">
                <label>Note *</label>
                <div class="rating-stars">
                    <button v-for="star in 5" :key="star" type="button" @click="form.rating = star" class="star-button"
                        :class="{ 'active': star <= form.rating }">
                        <AppIcon :name="star <= form.rating ? 'star' : 'star'" />
                    </button>
                </div>
                <span v-if="errors.rating" class="error-message">{{ errors.rating }}</span>
            </div>

            <div class="contact-form-group">
                <label for="category">Type de séjour</label>
                <select id="category" v-model="form.category">
                    <option value="all">Général</option>
                    <option value="couples">En couple</option>
                    <option value="families">En famille</option>
                    <option value="solo">Solo</option>
                    <option value="groups">En groupe</option>
                </select>
            </div>

            <div class="contact-form-group">
                <label for="testimonial_text">Votre témoignage * (minimum 20 caractères)</label>
                <textarea id="testimonial_text" v-model="form.testimonial_text"
                    :class="{ 'error': errors.testimonial_text }" rows="6"
                    placeholder="Racontez-nous votre expérience..." required></textarea>
                <span class="char-counter">{{ form.testimonial_text.length }} caractères</span>
                <span v-if="errors.testimonial_text" class="error-message">{{ errors.testimonial_text }}</span>
            </div>

            <!-- Upload de photos - PETITES et COMPACTES -->
            <div class="contact-form-group">
                <label>Photos de votre séjour (optionnel, max 3)</label>
                <div class="photos-upload-grid">
                    <ImageUpload v-for="i in 3" :key="`photo-${i}`" v-model="form.photos[i - 1]" variant="profile"
                        shape="square" size="sm" :placeholder-text="`Photo ${i}`" placeholder-icon="camera"
                        :show-actions="false" :enable-editing="true" @upload-start="handleUploadStart"
                        @upload-success="handleUploadSuccess" @upload-error="handleUploadError" />
                </div>
                <span class="help-text">Images au format JPG/PNG (max 2MB chacune)</span>
            </div>

            <button type="submit" class="btn-send" :disabled="submitting || uploading">
                <AppIcon name="loader-circle" :spin="true" v-if="submitting" />
                <AppIcon name="send" v-else />
                {{ submitting ? 'Envoi en cours...' : 'Envoyer mon avis' }}
            </button>
        </form>
    </div>
</template>

<script>
import ReviewsApi from '@/services/ReviewsApi'
import ImageUpload from '@/admin/components/ui/ImageUpload.vue'

export default {
    name: 'ReviewForm',
    components: { ImageUpload },

    data() {
        return {
            form: {
                client_name: '',
                email: '',
                location: '',
                testimonial_text: '',
                rating: 5,
                category: 'all',
                photos: [null, null, null]
            },
            errors: {},
            submitting: false,
            uploading: false,
            showSuccess: false
        }
    },

    methods: {
        handleUploadStart() {
            this.uploading = true
        },

        handleUploadSuccess(data) {
            this.uploading = false
            console.log('✅ Photo uploadée:', data)
        },

        handleUploadError(error) {
            this.uploading = false
            console.error('❌ Erreur upload:', error)
        },

        validateForm() {
            this.errors = {}

            if (!this.form.client_name || this.form.client_name.trim().length === 0) {
                this.errors.client_name = 'Le nom est requis'
            }

            if (!this.form.email || !this.isValidEmail(this.form.email)) {
                this.errors.email = 'L\'email doit être valide'
            }

            if (!this.form.testimonial_text || this.form.testimonial_text.length < 20) {
                this.errors.testimonial_text = 'Le témoignage doit contenir au moins 20 caractères'
            }

            if (!this.form.rating || this.form.rating < 1 || this.form.rating > 5) {
                this.errors.rating = 'Veuillez sélectionner une note'
            }

            return Object.keys(this.errors).length === 0
        },

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
            return emailRegex.test(email)
        },

        async submitReview() {
            if (!this.validateForm()) {
                return
            }

            this.submitting = true

            try {
                // Construire le tableau de photos (seulement celles qui ont une URL)
                const photos = this.form.photos
                    .filter(p => p && typeof p === 'string' && p.trim())
                    .map(url => ({ url, alt: `Photo de ${this.form.client_name}` }))

                const payload = {
                    client_name: this.form.client_name,
                    email: this.form.email,
                    location: this.form.location || null,
                    testimonial_text: this.form.testimonial_text,
                    rating: this.form.rating,
                    category: this.form.category,
                    photos: photos.length > 0 ? photos : []
                }

                await ReviewsApi.create(payload)

                this.showSuccess = true
                this.resetForm()
                this.$emit('review-submitted')

                setTimeout(() => {
                    this.showSuccess = false
                    this.$emit('close')
                }, 5000)

            } catch (error) {
                console.error('❌ Erreur lors de l\'envoi:', error)

                if (error.response?.status === 422) {
                    const serverErrors = error.response.data?.errors || {}
                    Object.keys(serverErrors).forEach(key => {
                        this.errors[key] = serverErrors[key][0]
                    })
                } else {
                    alert(error.response?.data?.message || 'Une erreur est survenue. Veuillez réessayer.')
                }
            } finally {
                this.submitting = false
            }
        },

        resetForm() {
            this.form = {
                client_name: '',
                email: '',
                location: '',
                testimonial_text: '',
                rating: 5,
                category: 'all',
                photos: [null, null, null]
            }
            this.errors = {}
        }
    }
}
</script>

<style scoped>
/* Grille compacte pour les 3 petites images */
.photos-upload-grid {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Styles minimes pour les étoiles */
.rating-stars {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    justify-content: center;
    background-color: #f9f9f9;
    padding: 0.1rem;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.star-button {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #ddd;
    cursor: pointer;
    padding: 0.25rem;
    transition: all 0.2s;
}

.star-button.active {
    color: #ffc107;
}

.star-button:hover {
    transform: scale(1.1);
}

.char-counter {
    display: block;
    text-align: right;
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.help-text {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 0.5rem;
}

.success-banner {
    background: linear-gradient(135deg, #2dce89 0%, #26c685 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
}

.success-banner i {
    font-size: 2rem;
}

.success-banner strong {
    display: block;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.success-banner p {
    margin: 0;
}
</style>