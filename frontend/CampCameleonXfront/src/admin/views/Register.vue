<template>
    <div class="register-page">
        <form class="register-form" @submit.prevent="handleRegister">
            <h1>Créer un compte</h1>
            <BaseInput v-model="firstName" placeholder="Prénom" required />
            <BaseInput v-model="lastName" placeholder="Nom" required />
            <BaseInput v-model="email" type="email" placeholder="Email" required />
            <BaseInput v-model="password" type="password" placeholder="Mot de passe" required />
            <BaseInput v-model="password" type="password" placeholder="Confirmer le mot de passe" required />
            <BaseButton type="submit" variant="primary" block :loading="auth.loading">
                S'inscrire
            </BaseButton>
            <p v-if="auth.error" class="error-message">{{ auth.error }}</p>
            <p class="switch-auth">Déjà un compte ? <router-link to="/admin/login">Connexion</router-link></p>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/shared/stores/auth'
import BaseInput from '@/shared/components/ui/BaseInput.vue'
import BaseButton from '@/shared/components/ui/BaseButton.vue'

defineOptions({
    name: 'AdminRegister'
})

const router = useRouter()
const auth = useAuthStore()

const firstName = ref('')
const lastName = ref('')
const email = ref('')
const password = ref('')

const handleRegister = async () => {
    try {
        await auth.register({ firstName: firstName.value, lastName: lastName.value, email: email.value, password: password.value })
        router.push('/home')
    } catch (error) {
        // handled by store
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

.error-message {
    color: #f5365c;
    margin-top: 1rem;
    text-align: center;
}
.switch-auth {
  margin-top: 1rem;
  text-align: center;
}
</style>