// Import des vues publiques
import PublicApp from '../PublicApp.vue'
import Home from '../views/Home.vue'
import About from '../views/About.vue'
import Services from '../views/Services.vue'
import Contact from '../views/Contact.vue'
import Testimonials from '../views/Testimonials.vue'

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
      },
      {
        path: 'testimonials',
        name: 'Testimonials',
        component: Testimonials
      },
      {
        path: '/edit-quote/:quoteId/:editToken',
        name: 'EditQuote',
        component: () => import('../views/EditQuote.vue')
      }
    ]
  }
]