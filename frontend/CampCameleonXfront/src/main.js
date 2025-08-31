import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import axios from 'axios'
import { useAuthStore } from './shared/stores/auth'

import '@fortawesome/fontawesome-free/css/all.css'
// Import des styles
import './assets/styles/shared.scss'

// Import de l'App principal
import App from './App.vue'

const app = createApp(App)

// Configure axios
axios.defaults.baseURL = 'http://localhost:8000'
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Ajoute axios à l'app
app.config.globalProperties.$http = axios

const pinia = createPinia()
app.use(pinia)

// 🔧 AJOUT : Intercepteur pour gérer les erreurs 401
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
      if (router.currentRoute.value.path !== '/admin/login') {
        router.push('/admin/login')
      }
    }
    return Promise.reject(error)
  }
)

app.use(router)

// 🔧 MODIFICATION : Vérifier l'auth APRÈS le mount pour éviter les erreurs
app.mount('#app')

// Vérification auth en différé
const authStore = useAuthStore()
authStore.checkAuth().catch(console.warn)