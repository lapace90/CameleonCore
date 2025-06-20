import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'

import '@fortawesome/fontawesome-free/css/all.css'
// Import des styles
import './assets/styles/shared.scss'

// Import de l'App principal (on le créera ensuite)
import App from './App.vue'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')