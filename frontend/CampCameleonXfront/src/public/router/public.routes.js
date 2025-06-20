// Import des vues publiques
import PublicApp from '../PublicApp.vue'
import Home from '../views/Home.vue'
import About from '../views/About.vue'
import Services from '../views/Services.vue'
import Contact from '../views/Contact.vue'

export default [
  {
    path: '/',
    component: PublicApp,
    children: [
      {
        path: 'home',
        name: 'Home',
        component: Home
      },
      {
        path: 'about',
        name: 'About',
        component: About
      },
      {
        path: 'services',
        name: 'Services',
        component: Services
      },
      {
        path: 'contact',
        name: 'Contact',
        component: Contact
      }
    ]
  }
]