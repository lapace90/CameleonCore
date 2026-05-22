// routes/public.routes.js

export default [
  {
    path: '/',
    component: () => import('../PublicApp.vue'),
    children: [
      {
        path: 'home',
        name: 'Home',
        component: () => import('../views/Home.vue'),
      },
      {
        path: 'about',
        name: 'About',
        component: () => import('../views/About.vue'),
      },
      {
        path: 'services',
        name: 'Services',
        component: () => import('../views/Services.vue'),
      },
      {
        path: 'contact',
        name: 'Contact',
        component: () => import('../views/Contact.vue'),
      },
      {
        path: 'privacy',
        name: 'Privacy',
        component: () => import('../views/Privacy.vue'),
      },
      {
        path: 'mentions',
        name: 'Mentions',
        component: () => import('../views/Mentions.vue'),
      },
      {
        path: 'cgu',
        name: 'CGU',
        component: () => import('../views/CGU.vue'),
      },
      {
        path: 'testimonials',
        name: 'Testimonials',
        component: () => import('../views/Testimonials.vue'),
      },

      // ⚠️ Chemin ABSOLU : restera en dehors du layout si tu gardes le slash.
      // Si tu veux qu'il soit *sous* PublicApp (ex: /edit-quote/...), enlève le slash initial.
      {
        path: '/edit-quote/:quoteId/:editToken',
        name: 'EditQuote',
        component: () => import('../views/EditQuote.vue'),
      },
    ],
  },
]
