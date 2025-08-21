<template>
    <div class="form-section">
        <label class="form-label">Image</label>
        <div class="image-upload-area">
            <!-- Logique d'upload extraite de ProductForm -->
            <div class="form-left">
                <div class="image-upload-section">
                    <h3>Image</h3>
                    <div class="image-upload-area">
                        <div v-if="imagePreview || form.image" class="main-image current-image">
                            <img :src="getValidImageUrl(product.image || form.image)" :alt="form.name || 'Image'"
                                @error="handleImageError" />
                            <div class="image-overlay">
                                <button type="button" @click="changeImage" class="overlay-btn">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" @click="removeImage" class="overlay-btn">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div v-else class="upload-placeholder" @click="selectImage">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Ajouter une image</p>
                        </div>
                        <input ref="imageInput" type="file" accept="image/*" @change="handleImageUpload"
                            style="display: none" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>

export default {
    name: 'ProductImageUpload',
    props: {
        modelValue: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            imagePreview: null
        };
    },
    methods: {
        getValidImageUrl(url) {
            return url ? url : '/images/default-product.png';
        },
        handleImageError(event) {
            event.target.src = '/images/default-product.png';
        },
        selectImage() {
            this.$refs.imageInput.click();
        },
        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.imagePreview = URL.createObjectURL(file);
                this.$emit('update:modelValue', file);
            }
        },
        changeImage() {
            this.selectImage();
        },
        removeImage() {
            this.imagePreview = null;
            this.$emit('update:modelValue', '');
        }
    }
};

</script>