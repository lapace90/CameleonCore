<template>
    <div class="login-page">
        <form class="login-form" @submit.prevent="handleLogin">
            <h1>Connexion administrateur</h1>
            <BaseInput v-model="email" type="email" label="Email" required />
            <BaseInput v-model="password" type="password" label="Mot de passe" required />
            <BaseButton type="submit" variant="primary" block :loading="auth.loading">
                Se connecter
            </BaseButton>
            <p v-if="auth.error" class="error-message">{{ auth.error }}</p>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/shared/stores/auth'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref('')
const password = ref('')

const handleLogin = async () => {
    try {
        await auth.login({ email: email.value, password: password.value })
        const redirect = route.query.redirect || '/admin/dashboard'
        router.push(redirect)
    } catch (error) {
        // l'erreur est gérée par le store
    }
}
</script>

<style scoped>
.login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fe;
}

.login-form {
    width: 100%;
    max-width: 400px;
    padding: 2rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    background: #fff;
    border-radius: 0.5rem;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
}

.login-form h1 {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    text-align: center;
    color: #32325d;
}

.error-message {
    color: #f5365c;
    margin-top: 1rem;
    text-align: center;
}
</style>