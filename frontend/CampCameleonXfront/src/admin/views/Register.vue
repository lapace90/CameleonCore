<template>
    <div class="register-page">
        <form class="register-form" @submit.prevent="handleRegister">
            <h1>Créer un compte</h1>
            
            <BaseInput 
                v-model="firstName" 
                placeholder="Prénom" 
                required 
                :disabled="loading"
            />
            
            <BaseInput 
                v-model="lastName" 
                placeholder="Nom" 
                required 
                :disabled="loading"
            />
            
            <BaseInput 
                v-model="email" 
                type="email" 
                placeholder="Email" 
                required 
                :disabled="loading"
            />
            
            <BaseInput 
                v-model="password" 
                type="password" 
                placeholder="Mot de passe (min. 8 caractères)" 
                required 
                :disabled="loading"
            />
            
            <BaseInput 
                v-model="passwordConfirmation" 
                type="password" 
                placeholder="Confirmer le mot de passe" 
                required 
                :disabled="loading"
            />
            
            <p v-if="validationError" class="error-message">
                {{ validationError }}
            </p>
            
            <BaseButton 
                type="submit" 
                variant="primary" 
                block 
                :loading="loading"
            >
                S'inscrire
            </BaseButton>
            
            <p v-if="auth.error" class="error-message">
                {{ auth.error }}
            </p>
            
            <p class="switch-auth">
                Déjà un compte ? 
                <router-link to="/admin/login">Connexion</router-link>
            </p>
        </form>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/shared/stores/auth'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'

defineOptions({
    name: 'AdminRegister'
})

const router = useRouter()
const auth = useAuthStore()

// Refs pour le formulaire
const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const validationError = ref('')

// Loading state
const loading = computed(() => auth.loading)

// Validation locale
const validateForm = () => {
    validationError.value = ''
    
    // Vérifier que tous les champs sont remplis
    if (!firstName.value.trim()) {
        validationError.value = 'Le prénom est requis'
        return false
    }
    
    if (!lastName.value.trim()) {
        validationError.value = 'Le nom est requis'
        return false
    }
    
    if (!email.value.trim()) {
        validationError.value = "L'email est requis"
        return false
    }
    
    if (password.value.length < 8) {
        validationError.value = 'Le mot de passe doit contenir au moins 8 caractères'
        return false
    }
    
    // Vérifier que les mots de passe correspondent
    if (password.value !== passwordConfirmation.value) {
        validationError.value = 'Les mots de passe ne correspondent pas'
        return false
    }
    
    return true
}

// Soumission du formulaire
const handleRegister = async () => {
    // Validation côté client
    if (!validateForm()) {
        return
    }
    
    try {
        const result = await auth.register({
            firstName: firstName.value,
            lastName: lastName.value,
            email: email.value,
            password: password.value
        })
        
        if (result.success) {
            console.log('✅ Inscription réussie, redirection...')
            // Rediriger vers le dashboard ou la page d'accueil
            router.push('/admin/dashboard')
        }
    } catch (error) {
        console.error('❌ Erreur lors de l\'inscription:', error)
        // L'erreur est déjà gérée par le store
    }
}
</script>

<style scoped>
.register-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fe;
}

.register-form {
    width: 100%;
    max-width: 500px;
    padding: 2rem;
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

.register-form h1 {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    text-align: center;
    color: #32325d;
}

.register-form .base-input {
    margin-bottom: 1rem;
}

.error-message {
    color: #f5365c;
    margin-top: 0.5rem;
    margin-bottom: 1rem;
    text-align: center;
    font-size: 0.875rem;
}

.switch-auth {
    margin-top: 1rem;
    text-align: center;
    color: #8898aa;
}

.switch-auth a {
    color: #5e72e4;
    text-decoration: none;
    font-weight: 600;
}

.switch-auth a:hover {
    text-decoration: underline;
}

.base-button {
    margin-top: 0.5rem;
}
</style>