import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import axios from 'axios'
import { useAuthStore } from './shared/stores/auth'

import '@fortawesome/fontawesome-free/css/all.css'
// Import des styles
import './assets/styles/shared.scss'

// Import de l'App principal (on le créera ensuite)
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

const authStore = useAuthStore()
authStore.checkAuth()

app.use(router)

app.mount('#app')